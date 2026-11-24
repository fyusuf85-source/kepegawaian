import './bootstrap';

const DB_NAME = 'PisAttendanceDB';
const STORE_NAME = 'queue';

function openQueue() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, 1);
        request.onupgradeneeded = () => {
            request.result.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function enqueue(record) {
    const db = await openQueue();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE_NAME, 'readwrite');
        const request = transaction.objectStore(STORE_NAME).add(record);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function pendingQueue() {
    const db = await openQueue();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE_NAME, 'readonly');
        const request = transaction.objectStore(STORE_NAME).getAll();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function removeQueued(id) {
    const db = await openQueue();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE_NAME, 'readwrite');
        const request = transaction.objectStore(STORE_NAME).delete(id);
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

window.PisAttendanceOffline = {
    save: async (form) => {
        const data = new FormData(form);
        const payload = {};
        for (const [key, value] of data.entries()) {
            payload[key] = value instanceof File ? value : String(value);
        }
        await enqueue({
            action: form.getAttribute('action'),
            payload,
            createdAt: new Date().toISOString(),
        });
        alert('Internet mati. Absensi disimpan di HP dan akan terkirim otomatis saat online.');
        form.reset();
        window.location.reload();
    },
    pending: pendingQueue,
    sync: async () => {
        const items = await pendingQueue();
        if (items.length === 0) return 0;
        
        let sent = 0;
        for (const item of items) {
            try {
                const body = new FormData();
                for (const [key, value] of Object.entries(item.payload)) {
                    body.append(key, value);
                }
                const response = await fetch(item.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body,
                });
                if (response.ok) {
                    await removeQueued(item.id);
                    sent++;
                }
            } catch (error) {
                console.error('Gagal sinkron:', error);
            }
        }
        if (sent > 0) {
            alert(`${sent} presensi tertunda berhasil dikirim.`);
            window.location.reload();
        }
        return sent;
    },
};

window.addEventListener('online', () => {
    window.PisAttendanceOffline.sync();
});
