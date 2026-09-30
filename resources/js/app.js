import Sortable from 'sortablejs';

import Alpine from 'alpinejs'
window.Alpine = Alpine
Alpine.start()


window.addEventListener('DOMContentLoaded', () => {
    const list1 = document.getElementById('available-exams');  // Exámenes disponibles (derecha)
    const list2 = document.getElementById('selected-exams');   // Exámenes seleccionados (izquierda)

    new Sortable(list1, {
        group: 'exams',
        animation: 150,
        onEnd(evt) {
            // Puedes capturar cuando se ha movido un ítem
            // evt.from, evt.to, evt.item
        }
    });

    new Sortable(list2, {
        group: 'exams',
        animation: 150,
        onEnd(evt) {
            // Igualmente, capturas el evento cuando el usuario suelta un examen en la lista izquierda
        }
    });
});
