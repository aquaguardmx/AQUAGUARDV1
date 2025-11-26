// src/js/courseEditor.js

// Store para manejar el estado del curso
let currentCourse = null;

// Inicializar el editor de curso
export function initializeCourseEditor(cursoData) {
    currentCourse = cursoData;
    console.log('Editor inicializado con curso:', currentCourse);
    
    // Configurar event listeners globales
    setupGlobalEventListeners();
}

// Configurar listeners globales
function setupGlobalEventListeners() {
    // Guardado automático
    let saveTimeout;
    
    // Escuchar cambios en los formularios
    document.addEventListener('input', (e) => {
        if (e.target.closest('[data-auto-save]')) {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                saveChanges();
            }, 1000);
        }
    });
    
    // Manejar envío de formularios
    document.addEventListener('submit', (e) => {
        e.preventDefault();
        saveChanges();
    });
}

// Función para guardar cambios
async function saveChanges() {
    if (!currentCourse) return;
    
    try {
        // Recopilar datos de todos los formularios
        const formData = collectFormData();
        
        const response = await fetch(`http://localhost:8000/api/cursos/${currentCourse.id_curso}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        if (response.ok) {
            showNotification('Cambios guardados correctamente', 'success');
        } else {
            throw new Error('Error al guardar');
        }
    } catch (error) {
        console.error('Error guardando curso:', error);
        showNotification('Error al guardar los cambios', 'error');
    }
}

// Recopilar datos de todos los formularios
function collectFormData() {
    const formData = {
        titulo: document.querySelector('[name="titulo"]')?.value || currentCourse.titulo,
        descripcion: document.querySelector('[name="descripcion"]')?.value || currentCourse.descripcion,
        categoria_id: document.querySelector('[name="categoria_id"]')?.value || currentCourse.categoria_id,
        nivel_dificultad: document.querySelector('[name="nivel_dificultad"]')?.value || currentCourse.nivel_dificultad,
        publicado: document.querySelector('[name="publicado"]')?.checked || currentCourse.publicado,
        // Agregar más campos según sea necesario
    };
    
    return formData;
}

// Mostrar notificaciones
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Auto-remover después de 3 segundos
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Exportar funciones para uso global
window.courseEditor = {
    saveChanges,
    showNotification,
    getCurrentCourse: () => currentCourse
};