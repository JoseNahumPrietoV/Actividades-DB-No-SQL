import subprocess
import time
import os
import sys

project_root = r"c:\xampp\htdocs\Proyecto-Sistema-de-entrega-de-comida-mongo"
out_dir = os.path.join(project_root, "tools", "vistas_capturas")
os.makedirs(out_dir, exist_ok=True)

server_port = 8092
php_cmd = ["php", "-S", f"127.0.0.1:{server_port}"]
print(f"Iniciando servidor PHP local en el puerto {server_port}...")
server = subprocess.Popen(php_cmd, cwd=project_root)
time.sleep(2)

vistas = [
    ("vista1_portada_index.png", f"http://127.0.0.1:{server_port}/router_captura.php?vista=portada"),
    ("vista2_catalogo_productos.png", f"http://127.0.0.1:{server_port}/router_captura.php?vista=productos"),
    ("vista3_login_otp.png", f"http://127.0.0.1:{server_port}/router_captura.php?vista=login"),
    ("vista4_registro.png", f"http://127.0.0.1:{server_port}/router_captura.php?vista=registro"),
    ("vista5_ver_tienda.png", f"http://127.0.0.1:{server_port}/router_captura.php?vista=ver_tienda"),
    ("vista6_carrito.png", f"http://127.0.0.1:{server_port}/router_captura.php?vista=carrito&as=cliente"),
    ("vista7_mis_pedidos.png", f"http://127.0.0.1:{server_port}/router_captura.php?vista=mis_pedidos&as=cliente"),
    ("vista8_dashboard_vendedor.png", f"http://127.0.0.1:{server_port}/router_captura.php?vista=dashboard_vendedor&as=vendedor"),
    ("vista9_mis_platillos.png", f"http://127.0.0.1:{server_port}/router_captura.php?vista=mis_platillos&as=vendedor"),
    ("vista10_pedidos_pendientes.png", f"http://127.0.0.1:{server_port}/router_captura.php?vista=pedidos_pendientes&as=vendedor"),
    ("vista11_historial_usuarios.png", f"http://127.0.0.1:{server_port}/router_captura.php?vista=historial_usuarios&as=admin")
]

edge_exe = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"

for filename, url in vistas:
    target_path = os.path.join(out_dir, filename)
    print(f"Capturando {filename}...")
    edge_cmd = [
        edge_exe,
        "--headless=new",
        "--disable-gpu",
        "--window-size=1280,820",
        f"--screenshot={target_path}",
        url
    ]
    res = subprocess.run(edge_cmd, capture_output=True, timeout=20)
    if os.path.exists(target_path):
        size = os.path.getsize(target_path)
        print(f"  -> OK: {filename} generado ({size} bytes)")
    else:
        print(f"  -> ERROR: {filename} no se creo.")

server.terminate()
print("Capturas finalizadas y servidor cerrado.")
