// Front-end/js/formularioculto.js
// Control reactivo unificado de alternancia entre formularios Cliente y Vendedor

document.addEventListener('DOMContentLoaded', function() {
    const radioCliente = document.getElementById('cliente');
    const radioVendedor = document.getElementById('vendedor');
    const formCliente = document.getElementById('form-cliente');
    const formVendedor = document.getElementById('form-vendedor');

    if (!radioCliente || !radioVendedor || !formCliente || !formVendedor) return;

    function actualizarFormularios() {
        if (radioCliente.checked) {
            formCliente.style.display = 'block';
            formCliente.classList.remove('hidden');
            formVendedor.style.display = 'none';
            formVendedor.classList.add('hidden');

            habilitarCampos(formCliente, true);
            habilitarCampos(formVendedor, false);
        } else {
            formCliente.style.display = 'none';
            formCliente.classList.add('hidden');
            formVendedor.style.display = 'block';
            formVendedor.classList.remove('hidden');

            habilitarCampos(formCliente, false);
            habilitarCampos(formVendedor, true);
        }
    }

    function habilitarCampos(formSection, habilitar) {
        const inputs = formSection.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.disabled = !habilitar;
            input.required = habilitar;
        });
    }

    radioCliente.addEventListener('change', actualizarFormularios);
    radioVendedor.addEventListener('change', actualizarFormularios);

    actualizarFormularios();
});