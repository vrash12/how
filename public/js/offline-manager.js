class OfflineManager {
  constructor() {
    this.DB_NAME = 'PatientCareOfflineDB';
    this.DB_VERSION = 1;
    this.db = null;
    this.pendingQueue = 'pendingRequests';
    this.patientsStore = 'patients';
    this.online = window.navigator.onLine;
    
    // Initialize
    this.initDB();
    this.setupEventListeners();
  }

  setupEventListeners() {
    // Handle online/offline events
    window.addEventListener('online', () => {
      console.log('Back online');
      this.online = true;
      this.syncPendingRequests();
      this.showToast('Internet connection restored! Syncing data...');
    });

    window.addEventListener('offline', () => {
      console.log('Went offline');
      this.online = false;
      this.showToast('You are offline. Changes will be saved locally.');
    });
  }

  showToast(message, type = 'info') {
    // Use SweetAlert2 if available
    if (window.Swal) {
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
      });
    } else {
      // Fallback to alert
      alert(message);
    }
  }

  initDB() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);
      
      request.onerror = (event) => {
        console.error('IndexedDB error:', event.target.error);
        reject('Could not initialize offline database');
      };
      
      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        
        // Create queue for pending API requests
        if (!db.objectStoreNames.contains(this.pendingQueue)) {
          db.createObjectStore(this.pendingQueue, { keyPath: 'id', autoIncrement: true });
        }
        
        // Store for offline patients
        if (!db.objectStoreNames.contains(this.patientsStore)) {
          const patientsStore = db.createObjectStore(this.patientsStore, { keyPath: 'tempId', autoIncrement: true });
          patientsStore.createIndex('patientId', 'patient_id', { unique: false });
          patientsStore.createIndex('synced', 'synced', { unique: false });
          patientsStore.createIndex('createdAt', 'createdAt', { unique: false });
        }
      };
      
      request.onsuccess = (event) => {
        this.db = event.target.result;
        console.log('IndexedDB initialized successfully');
        resolve(this.db);
      };
    });
  }

  async getDBInstance() {
    if (this.db) return this.db;
    return await this.initDB();
  }

  // Save a form submission to IndexedDB
  async saveFormOffline(formData, endpoint, method = 'POST') {
    try {
      const db = await this.getDBInstance();
      
      // Create a timestamp and unique ID
      const timestamp = new Date().toISOString();
      const tempId = 'temp_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);
      
      // Parse form data into an object
      const data = {};
      formData.forEach((value, key) => {
        data[key] = value;
      });
      
      // Add metadata
      const record = {
        tempId,
        ...data,
        createdAt: timestamp,
        synced: false,
        endpoint,
        method
      };
      
      // Save to patients store
      const tx = db.transaction(this.patientsStore, 'readwrite');
      const store = tx.objectStore(this.patientsStore);
      await store.add(record);
      
      // Also add to pending requests queue
      const queueTx = db.transaction(this.pendingQueue, 'readwrite');
      const queue = queueTx.objectStore(this.pendingQueue);
      await queue.add({
        endpoint,
        method,
        data,
        createdAt: timestamp,
        recordId: tempId
      });
      
      return {
        success: true,
        message: 'Saved offline. Will sync when back online.',
        tempId
      };
    } catch (error) {
      console.error('Error saving form offline:', error);
      return {
        success: false,
        error: error.message || 'Could not save data offline'
      };
    }
  }

  // Synchronize all pending requests when back online
  async syncPendingRequests() {
    if (!this.online) return false;
    
    try {
      const db = await this.getDBInstance();
      const tx = db.transaction(this.pendingQueue, 'readonly');
      const store = tx.objectStore(this.pendingQueue);
      const requests = await store.getAll();
      
      if (!requests.length) {
        console.log('No pending requests to sync');
        return true;
      }
      
      console.log(`Syncing ${requests.length} pending requests`);
      
      for (const request of requests) {
        try {
          // Add CSRF token if needed for Laravel
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
          
          // Send the request
          const response = await fetch(request.endpoint, {
            method: request.method,
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'X-Offline-Sync': 'true'
            },
            body: JSON.stringify(request.data)
          });
          
          if (!response.ok) {
            throw new Error(`Server responded with ${response.status}`);
          }
          
          const result = await response.json();
          
          // Update the patient record to mark as synced
          if (request.recordId) {
            const patientTx = db.transaction(this.patientsStore, 'readwrite');
            const patientStore = patientTx.objectStore(this.patientsStore);
            const patient = await patientStore.get(request.recordId);
            
            if (patient) {
              patient.synced = true;
              patient.syncedAt = new Date().toISOString();
              if (result.patient_id) {
                patient.patient_id = result.patient_id;
              }
              await patientStore.put(patient);
            }
          }
          
          // Remove from pending queue
          const deleteTx = db.transaction(this.pendingQueue, 'readwrite');
          const deleteStore = deleteTx.objectStore(this.pendingQueue);
          await deleteStore.delete(request.id);
          
          console.log(`Synced request ${request.id} successfully`);
        } catch (error) {
          console.error(`Failed to sync request ${request.id}:`, error);
          // We don't remove failed requests, so they can be retried later
        }
      }
      
      // Check if we have more pending requests
      const checkTx = db.transaction(this.pendingQueue, 'readonly');
      const checkStore = checkTx.objectStore(this.pendingQueue);
      const remaining = await checkStore.count();
      
      if (remaining === 0) {
        this.showToast('All data synced successfully!', 'success');
        return true;
      } else {
        this.showToast(`Synced some data. ${remaining} items still pending.`, 'warning');
        return false;
      }
    } catch (error) {
      console.error('Error syncing pending requests:', error);
      this.showToast('Error syncing data', 'error');
      return false;
    }
  }

  // Get all locally stored patients
  async getOfflinePatients() {
    try {
      const db = await this.getDBInstance();
      const tx = db.transaction(this.patientsStore, 'readonly');
      const store = tx.objectStore(this.patientsStore);
      return await store.getAll();
    } catch (error) {
      console.error('Error getting offline patients:', error);
      return [];
    }
  }

  // Get pending sync count
  async getPendingSyncCount() {
    try {
      const db = await this.getDBInstance();
      const tx = db.transaction(this.pendingQueue, 'readonly');
      const store = tx.objectStore(this.pendingQueue);
      return await store.count();
    } catch (error) {
      console.error('Error getting pending sync count:', error);
      return 0;
    }
  }
}

// Initialize and export
const offlineManager = new OfflineManager();
window.offlineManager = offlineManager;