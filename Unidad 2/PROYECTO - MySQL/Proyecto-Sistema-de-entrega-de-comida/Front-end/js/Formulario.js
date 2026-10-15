//formulario 

document.addEventListener('DOMContentLoaded', function() {
    // 1. Seleccionamos los elementos que vamos a necesitar
    const radioCliente = document.getElementById('cliente');
    const radioVendedor = document.getElementById('vendedor');
    
    const formCliente = document.getElementById('form-cliente');
    const formVendedor = document.getElementById('form-vendedor');

    // Función para manejar la visibilidad
    function toggleForms() {
        if (radioCliente.checked) {
            // Si Cliente está seleccionado, muestra su form y oculta el de vendedor
            formCliente.classList.remove('hidden');
            formVendedor.classList.add('hidden');
            
            // Hacemos que los campos de cliente sean requeridos y los de vendedor no
            formCliente.querySelectorAll('input').forEach(input => input.required = true);
            formVendedor.querySelectorAll('input').forEach(input => input.required = false);

        } else if (radioVendedor.checked) {
            // Si Vendedor está seleccionado, hacemos lo contrario
            formCliente.classList.add('hidden');
            formVendedor.classList.remove('hidden');

            // Hacemos que los campos de vendedor sean requeridos y los de cliente no
            formCliente.querySelectorAll('input').forEach(input => input.required = false);
            formVendedor.querySelectorAll('input').forEach(input => input.required = true);
        }
    }

    // 2. Añadimos un "escuchador" para que la función se ejecute cuando cambie la selección
    radioCliente.addEventListener('change', toggleForms);
    radioVendedor.addEventListener('change', toggleForms);
});