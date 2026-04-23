const { useState, useEffect, useCallback } = React;

function ToastNotification({ id, msg, type, onClose }) {
  useEffect(() => {
    const timer = setTimeout(() => {
      onClose(id);
    }, 4000);
    return () => clearTimeout(timer);
  }, [id, onClose]);

  const icon = type === 'success' ? '✅' : (type === 'error' ? '❌' : 'ℹ️');

  return (
    <div className={`flash flash-${type}`}>
      <span>{icon}</span>
      <span>{msg}</span>
    </div>
  );
}

function ToastContainer() {
  const [toasts, setToasts] = useState([]);

  // Provide a global function for other scripts to call
  useEffect(() => {
    window.showToast = (msg, type = 'success') => {
      const id = Date.now() + Math.random().toString();
      setToasts(prev => [...prev, { id, msg, type }]);
    };

    // Check if there's a server-side flash message waiting
    if (window.SERVER_FLASH) {
      window.showToast(window.SERVER_FLASH.msg, window.SERVER_FLASH.type);
      window.SERVER_FLASH = null;
    }
  }, []);

  const removeToast = useCallback((id) => {
    setToasts(prev => prev.filter(t => t.id !== id));
  }, []);

  return (
    <div className="flash-container">
      {toasts.map(toast => (
        <ToastNotification 
          key={toast.id}
          id={toast.id}
          msg={toast.msg}
          type={toast.type}
          onClose={removeToast}
        />
      ))}
    </div>
  );
}

const rootElement = document.getElementById('react-toast-root');
if (rootElement) {
  const root = ReactDOM.createRoot(rootElement);
  root.render(<ToastContainer />);
}
