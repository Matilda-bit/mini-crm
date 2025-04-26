window.showToast = function (message, type = 'success') {
    let toastContainer = document.getElementById('toast-container');

    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.position = 'fixed';
        toastContainer.style.bottom = '20px';
        toastContainer.style.right = '20px';
        toastContainer.style.zIndex = '9999';
        toastContainer.style.display = 'flex';
        toastContainer.style.flexDirection = 'column';
        toastContainer.style.gap = '10px';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = `custom-toast ${type}`;
    toast.style.padding = '10px 15px';
    toast.style.borderRadius = '5px';
    toast.style.color = '#fff';
    toast.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
    toast.style.minWidth = '200px';
    toast.style.fontSize = '14px';
    toast.style.opacity = '0.85';
    toast.style.transition = 'opacity 0.3s ease';

    toast.style.backgroundColor = type === 'danger' ? '#dc3545' :
                                  type === 'warning' ? '#ffc107' :
                                  type === 'info' ? '#17a2b8' : '#28a745';

    toast.innerText = message;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
    }, 4000);
};
