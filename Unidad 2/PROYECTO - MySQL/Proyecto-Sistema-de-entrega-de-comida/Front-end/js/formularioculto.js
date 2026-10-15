document.addEventListener('DOMContentLoaded', function() {
                            // Referencias a los elementos del DOM
                            const radioCliente = document.getElementById('cliente');
                            const radioVendedor = document.getElementById('vendedor');
                            const formCliente = document.getElementById('form-cliente');
                            const formVendedor = document.getElementById('form-vendedor');

                            // Función para actualizar el estado de los formularios
                            function actualizarFormularios() {
                                if (radioCliente.checked) {
                                    // Mostrar formulario de cliente, ocultar el de vendedor
                                    formCliente.style.display = 'block';
                                    formVendedor.style.display = 'none';

                                    // Activar campos de cliente y DESACTIVAR los de vendedor
                                    habilitarCampos(formCliente, true);
                                    habilitarCampos(formVendedor, false);
                                } else {
                                    // Mostrar formulario de vendedor, ocultar el de cliente
                                    formCliente.style.display = 'none';
                                    formVendedor.style.display = 'block';

                                    // Activar campos de vendedor y DESACTIVAR los de cliente
                                    habilitarCampos(formCliente, false);
                                    habilitarCampos(formVendedor, true);
                                }
                            }

                            // Función que activa/desactiva los inputs de una sección
                            function habilitarCampos(formSection, habilitar) {
                                const inputs = formSection.querySelectorAll('input, select, textarea');
                                inputs.forEach(input => {
                                    // Los campos de email y password son compartidos, no deben desactivarse
                                    if (input.name !== 'email' && input.name !== 'password' && input.name !== 'confirm_password') {
                                        input.disabled = !habilitar;
                                    }
                                    // El atributo 'required' se maneja para todos los inputs de la sección
                                    input.required = habilitar;
                                });
                            }

                            // Añadir listeners a los radio buttons para que reaccionen al cambio
                            radioCliente.addEventListener('change', actualizarFormularios);
                            radioVendedor.addEventListener('change', actualizarFormularios);

                            // Ejecutar la función al cargar la página para establecer el estado inicial correcto
                            actualizarFormularios();
                        });