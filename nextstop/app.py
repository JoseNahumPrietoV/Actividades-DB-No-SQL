import os
import re
import random
import uuid
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime
from functools import wraps
from flask import Flask, render_template, request, redirect, url_for, flash, session, abort, jsonify
from pymongo import MongoClient
from bson.objectid import ObjectId
from werkzeug.security import generate_password_hash, check_password_hash
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)
app.secret_key = os.getenv("SECRET_KEY", "dev_secret_key_itver")

# Conexión MongoDB Atlas
client = MongoClient(os.getenv("MONGO_URI"))
db = client["NextStop"]
usuarios_col = db["usuarios"]
vuelos_col = db["vuelos"]
reservas_col = db["reservas"]
favoritos_col = db["favoritos"]

PATRON_TELEFONO = r"^[0-9]{3}-[0-9]{3}-[0-9]{4}$"

AEROLINEAS_POOL = [
    {"codigo": "AM", "nombre": "AeroMéxico"},
    {"codigo": "VB", "nombre": "VivaAerobus"},
    {"codigo": "Y4", "nombre": "Volaris"},
    {"codigo": "IB", "nombre": "Iberia"},
    {"codigo": "AA", "nombre": "American Airlines"},
    {"codigo": "AF", "nombre": "Air France"},
    {"codigo": "DL", "nombre": "Delta Air Lines"},
    {"codigo": "AV", "nombre": "Avianca"},
    {"codigo": "AE", "nombre": "Air Europa"}
]

TODOS_LOS_ASIENTOS = [f"{fila}{col}" for fila in range(1, 10) for col in ['A', 'B', 'C', 'D', 'E', 'F']]


# --- SERVICIO DE CORREO ELECTRÓNICO (SMTP TLS) ---

def enviar_correo_notificacion(destinatario, asunto, cuerpo_html):
    remitente = os.getenv("MAIL_USERNAME")
    password = os.getenv("MAIL_PASSWORD")

    if not remitente or not password or not destinatario or "@" not in destinatario:
        return False

    destinatario_limpio = destinatario.strip()
    mensaje = MIMEMultipart("alternative")
    mensaje["Subject"] = asunto
    mensaje["From"] = f"NextStop Viajes <{remitente}>"
    mensaje["To"] = destinatario_limpio
    mensaje["Reply-To"] = remitente
    mensaje["X-Mailer"] = "Python-SMTPLib"

    parte_html = MIMEText(cuerpo_html, "html", "utf-8")
    mensaje.attach(parte_html)

    try:
        servidor = smtplib.SMTP(os.getenv("MAIL_SERVER", "smtp.gmail.com"), int(os.getenv("MAIL_PORT", 587)),
                                timeout=15)
        servidor.ehlo()
        servidor.starttls()
        servidor.ehlo()
        servidor.login(remitente, password)
        servidor.sendmail(remitente, [destinatario_limpio], mensaje.as_string())
        servidor.quit()
        print(f"✓ Correo enviado exitosamente a: {destinatario_limpio}")
        return True
    except Exception as e:
        print(f"✗ Error al enviar correo a {destinatario_limpio}: {e}")
        return False


# --- FUNCIONES AUXILIARES ---

def obtener_codigo_iata(texto):
    coincidencia = re.search(r'\(([A-Z]{3})\)', texto.upper())
    if coincidencia:
        return coincidencia.group(1)
    letras = re.sub(r'[^A-Za-z]', '', texto).upper()
    return letras[:3] if len(letras) >= 3 else "NXT"


def simular_vuelos_api(origen, destino, fecha):
    hoy_str = datetime.utcnow().strftime("%Y-%m-%d")
    fecha_final = fecha if (fecha and fecha >= hoy_str) else hoy_str
    origen_final = origen if origen else "Ciudad de México (MEX)"
    destino_final = destino if destino else "Destino Internacional"

    cod_orig = obtener_codigo_iata(origen_final)
    cod_dest = obtener_codigo_iata(destino_final)

    vuelos_generados = []
    cantidad = random.randint(3, 5)

    for _ in range(cantidad):
        aerolinea = random.choice(AEROLINEAS_POOL)
        hora = f"{random.randint(5, 23):02d}:{random.choice(['00', '15', '30', '45'])} HRS"
        precio = round(random.uniform(1800.0, 18500.0), 1)
        asientos_disp = random.randint(10, 45)
        cant_ocupados = len(TODOS_LOS_ASIENTOS) - asientos_disp
        asientos_ocupados_azar = random.sample(TODOS_LOS_ASIENTOS, cant_ocupados)

        vuelo_doc = {
            "aerolinea_codigo": aerolinea["codigo"],
            "aerolinea_nombre": aerolinea["nombre"],
            "codigo_origen": cod_orig,
            "origen": origen_final,
            "codigo_destino": cod_dest,
            "destino": destino_final,
            "hora_salida": hora,
            "hora_llegada": "04:45",
            "duracion": "11h 15m",
            "clase": "ECONÓMICA",
            "fecha": fecha_final,
            "precio": precio,
            "moneda": "MXN",
            "asientos_disponibles": asientos_disp,
            "asientos_ocupados": asientos_ocupados_azar,
            "tipo_vuelo": "VUELO",
            "estado": "A TIEMPO",
            "generado_por_api": True
        }
        vuelos_generados.append(vuelo_doc)

    if vuelos_generados:
        resultado = vuelos_col.insert_many(vuelos_generados)
        for idx, doc_id in enumerate(resultado.inserted_ids):
            vuelos_generados[idx]["_id"] = doc_id

    return vuelos_generados


def login_requerido(rol_permitido):
    def decorador(f):
        @wraps(f)
        def funcion_decorada(*args, **kwargs):
            if "user_id" not in session:
                flash("Debes iniciar sesión para continuar.", "error")
                return redirect(url_for("index"))
            if session.get("rol") != rol_permitido:
                flash("Acceso denegado a este módulo.", "error")
                return redirect(url_for("index"))
            return f(*args, **kwargs)

        return funcion_decorada

    return decorador


# --- RUTAS DE ACCESO Y REGISTRO ---

@app.route("/")
def index():
    return render_template("auth/index.html")


@app.route("/registro/proveedor", methods=["GET", "POST"])
def registro_proveedor():
    if request.method == "POST":
        nombre_agencia = request.form.get("nombre_agencia", "").strip()
        admin_agencia = request.form.get("admin_agencia", "").strip()
        correo = request.form.get("correo", "").strip().lower()
        telefono = request.form.get("telefono", "").strip()
        sede = request.form.get("sede", "")
        password = request.form.get("password", "")
        password_confirm = request.form.get("password_confirm", "")

        if password != password_confirm:
            flash("Las contraseñas no coinciden.", "error")
            return redirect(url_for("registro_proveedor"))

        if not re.match(PATRON_TELEFONO, telefono):
            flash("El formato de teléfono debe ser XXX-XXX-XXXX y contener únicamente dígitos.", "error")
            return redirect(url_for("registro_proveedor"))

        if usuarios_col.find_one({"correo": correo, "rol": "proveedor"}):
            flash("Ya existe una agencia registrada con este correo.", "error")
            return redirect(url_for("registro_proveedor"))

        documento_agencia = {
            "nombre": nombre_agencia,
            "admin_responsable": admin_agencia,
            "correo": correo,
            "telefono": telefono,
            "sede": sede,
            "password_hash": generate_password_hash(password),
            "rol": "proveedor",
            "creado_en": datetime.utcnow()
        }
        usuarios_col.insert_one(documento_agencia)
        flash("Agencia registrada exitosamente. Ya puedes iniciar sesión.", "success")
        return redirect(url_for("login", tipo="proveedor"))

    return render_template("auth/registro_proveedor.html")


@app.route("/registro/cliente", methods=["GET", "POST"])
def registro_cliente():
    if request.method == "POST":
        nombre = request.form.get("nombre", "").strip()
        correo = request.form.get("correo", "").strip().lower()
        telefono = request.form.get("telefono", "").strip()
        password = request.form.get("password", "")
        password_confirm = request.form.get("password_confirm", "")

        if password != password_confirm:
            flash("Las contraseñas no coinciden.", "error")
            return redirect(url_for("registro_cliente"))

        if not re.match(PATRON_TELEFONO, telefono):
            flash("El formato de teléfono debe ser XXX-XXX-XXXX y contener únicamente dígitos.", "error")
            return redirect(url_for("registro_cliente"))

        if usuarios_col.find_one({"correo": correo, "rol": "cliente"}):
            flash("Este correo ya está registrado como cliente.", "error")
            return redirect(url_for("registro_cliente"))

        documento_cliente = {
            "nombre": nombre,
            "correo": correo,
            "telefono": telefono,
            "password_hash": generate_password_hash(password),
            "rol": "cliente",
            "creado_en": datetime.utcnow()
        }
        usuarios_col.insert_one(documento_cliente)

        html_bienvenida = f"""
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: #ea580c; margin-bottom: 5px;">¡Bienvenido a NextStop! ✈</h2>
                <p style="color: #64748b; font-size: 14px;">Tu cuenta de viajero ha sido creada exitosamente</p>
            </div>
            <p>Hola <b>{nombre}</b>,</p>
            <p>Ya puedes iniciar sesión en la plataforma para buscar vuelos en tiempo real, reservar boletos y gestionar tus viajes.</p>
            <div style="background-color: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0; border: 1px solid #e2e8f0;">
                <p style="margin: 4px 0; font-size: 13px;"><b>Correo registrado:</b> {correo}</p>
                <p style="margin: 4px 0; font-size: 13px;"><b>Teléfono:</b> {telefono}</p>
            </div>
            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">
            <p style="font-size: 11px; color: #94a3b8; text-align: center;">NextStop &bull; Seguridad con Cifrado en Tránsito TLS.</p>
        </div>
        """
        enviar_correo_notificacion(correo, "¡Bienvenido a NextStop! Registro Exitoso", html_bienvenida)

        flash("Cuenta de viajero creada exitosamente. Revisa tu correo electrónico.", "success")
        return redirect(url_for("login", tipo="cliente"))

    return render_template("auth/registro_cliente.html")


@app.route("/login/<tipo>", methods=["GET", "POST"])
def login(tipo):
    if tipo not in ["proveedor", "cliente"]:
        return redirect(url_for("index"))

    if request.method == "POST":
        correo = request.form.get("correo", "").strip().lower()
        password = request.form.get("password", "")

        usuario = usuarios_col.find_one({"correo": correo, "rol": tipo})
        if usuario and check_password_hash(usuario.get("password_hash", ""), password):
            session["user_id"] = str(usuario["_id"])
            session["nombre"] = usuario.get("nombre")
            session["correo"] = usuario.get("correo")
            session["telefono"] = usuario.get("telefono", "")
            session["rol"] = tipo

            if tipo == "proveedor":
                return redirect(url_for("dashboardProveedor"))
            else:
                return redirect(url_for("dashboardCliente"))

        flash("Correo o contraseña incorrectos.", "error")

    return render_template("auth/auth.html", tipo=tipo)


@app.route("/logout")
def logout():
    session.clear()
    return redirect(url_for("index"))


# --- APIS Y RUTAS DEL CLIENTE ---

@app.route("/api/aeropuertos")
@login_requerido("cliente")
def api_aeropuertos():
    origenes = vuelos_col.distinct("origen")
    destinos = vuelos_col.distinct("destino")
    aeropuertos = sorted(list(set(origenes + destinos)))
    return {"aeropuertos": aeropuertos}


@app.route("/api/favoritos/toggle/<vuelo_id>", methods=["POST"])
@login_requerido("cliente")
def toggle_favorito(vuelo_id):
    try:
        user_id = ObjectId(session["user_id"])
        vuelo_obj_id = ObjectId(vuelo_id)
    except Exception:
        return jsonify({"success": False, "error": "Identificador inválido"}), 400

    filtro = {"usuario_id": user_id, "vuelo_id": vuelo_obj_id}
    favorito_existente = favoritos_col.find_one(filtro)

    if favorito_existente:
        favoritos_col.delete_one(filtro)
        es_favorito = False
    else:
        favoritos_col.insert_one({
            "usuario_id": user_id,
            "vuelo_id": vuelo_obj_id,
            "fecha_agregado": datetime.utcnow()
        })
        es_favorito = True

    return jsonify({"success": True, "es_favorito": es_favorito})


@app.route("/favoritos")
@login_requerido("cliente")
def favoritos():
    user_id = ObjectId(session["user_id"])
    registros_favs = list(favoritos_col.find({"usuario_id": user_id}))
    vuelos_ids = [reg["vuelo_id"] for reg in registros_favs]
    vuelos_favs = list(vuelos_col.find({"_id": {"$in": vuelos_ids}})) if vuelos_ids else []
    return render_template("cliente/favoritos.html", nombre=session.get("nombre"), vuelos=vuelos_favs)


@app.route("/dashboard/cliente")
@login_requerido("cliente")
def dashboardCliente():
    hoy_str = datetime.utcnow().strftime("%Y-%m-%d")
    origen_query = request.args.get("origen", "").strip()
    destino_query = request.args.get("destino", "").strip()
    fecha_query = request.args.get("fecha", "").strip()

    es_peticion_busqueda = "origen" in request.args or "destino" in request.args or "fecha" in request.args
    vuelos = []

    if es_peticion_busqueda:
        criterios = []
        if fecha_query:
            fecha_efectiva = max(fecha_query, hoy_str)
            criterios.append({"fecha": fecha_efectiva})
        else:
            criterios.append({"fecha": {"$gte": hoy_str}})

        if origen_query:
            criterios.append({
                "$or": [
                    {"origen": {"$regex": origen_query, "$options": "i"}},
                    {"codigo_origen": {"$regex": origen_query, "$options": "i"}}
                ]
            })

        if destino_query:
            criterios.append({
                "$or": [
                    {"destino": {"$regex": destino_query, "$options": "i"}},
                    {"codigo_destino": {"$regex": destino_query, "$options": "i"}}
                ]
            })

        filtro_final = {"$and": criterios} if criterios else {"fecha": {"$gte": hoy_str}}
        # Búsqueda en los vuelos ya guardados (persistencia)
        vuelos = list(vuelos_col.find(filtro_final).sort("fecha", 1).limit(25))

        # Solo si después de buscar exhaustivamente no hay ningún vuelo existente, se simulan nuevos
        if not vuelos and (origen_query or destino_query):
            fecha_sim = fecha_query if (fecha_query and fecha_query >= hoy_str) else hoy_str
            vuelos = simular_vuelos_api(origen_query, destino_query, fecha_sim)
        elif not vuelos and not origen_query and not destino_query:
            vuelos = list(vuelos_col.find({"fecha": {"$gte": hoy_str}}).sort("fecha", 1).limit(15))

    user_id = ObjectId(session["user_id"])
    favoritos_docs = list(favoritos_col.find({"usuario_id": user_id}))
    favoritos_ids = [str(f["vuelo_id"]) for f in favoritos_docs]

    return render_template(
        "cliente/dashboardCliente.html",
        nombre=session.get("nombre"),
        vuelos=vuelos,
        hay_busqueda=es_peticion_busqueda,
        origen=origen_query,
        destino=destino_query,
        fecha=fecha_query,
        fecha_minima=hoy_str,
        favoritos_ids=favoritos_ids
    )


# --- MÓDULO MI PERFIL (EDICIÓN Y ACTUALIZACIÓN EN ATLAS) ---

@app.route("/perfil", methods=["GET", "POST"])
@login_requerido("cliente")
def perfil():
    user_id = ObjectId(session["user_id"])
    usuario = usuarios_col.find_one({"_id": user_id})

    if request.method == "POST":
        nuevo_nombre = request.form.get("nombre", "").strip()
        nuevo_correo = request.form.get("correo", "").strip().lower()
        nuevo_telefono = request.form.get("telefono", "").strip()
        nueva_password = request.form.get("password", "").strip()
        confirm_password = request.form.get("password_confirm", "").strip()

        # Validaciones
        if not nuevo_nombre:
            flash("El nombre no puede estar vacío.", "error")
            return redirect(url_for("perfil"))

        if not re.match(PATRON_TELEFONO, nuevo_telefono):
            flash("El formato de teléfono debe ser XXX-XXX-XXXX con 10 dígitos.", "error")
            return redirect(url_for("perfil"))

        # Si cambió el correo, verificar que no esté ocupado por otro usuario
        if nuevo_correo != usuario.get("correo"):
            if usuarios_col.find_one({"correo": nuevo_correo, "_id": {"$ne": user_id}}):
                flash("Ese correo electrónico ya está registrado por otro usuario.", "error")
                return redirect(url_for("perfil"))

        datos_actualizar = {
            "nombre": nuevo_nombre,
            "correo": nuevo_correo,
            "telefono": nuevo_telefono
        }

        # Cambio opcional de contraseña
        if nueva_password or confirm_password:
            if nueva_password != confirm_password:
                flash("Las nuevas contraseñas no coinciden.", "error")
                return redirect(url_for("perfil"))
            if len(nueva_password) < 6:
                flash("La contraseña debe tener al menos 6 caracteres.", "error")
                return redirect(url_for("perfil"))
            datos_actualizar["password_hash"] = generate_password_hash(nueva_password)

        # Actualización en MongoDB Atlas
        usuarios_col.update_one({"_id": user_id}, {"$set": datos_actualizar})

        # Actualizar sesión activa
        session["nombre"] = nuevo_nombre
        session["correo"] = nuevo_correo
        session["telefono"] = nuevo_telefono

        flash("¡Información actualizada con éxito en la base de datos!", "success")
        return redirect(url_for("perfil"))

    return render_template("cliente/perfil.html", nombre=session.get("nombre"), usuario=usuario)


# --- MÓDULO MIS BOLETOS Y CANCELACIÓN ---

@app.route("/mis-boletos")
@login_requerido("cliente")
def mis_boletos():
    user_id = ObjectId(session["user_id"])
    reservas = list(reservas_col.find({"usuario_id": user_id}).sort("creado_en", -1))
    return render_template("cliente/misBoletos.html", nombre=session.get("nombre"), reservas=reservas)


@app.route("/api/reservas/cancelar/<reserva_id>", methods=["POST"])
@login_requerido("cliente")
def cancelar_reserva(reserva_id):
    try:
        user_id = ObjectId(session["user_id"])
        reserva_obj_id = ObjectId(reserva_id)
    except Exception:
        return jsonify({"success": False, "error": "ID de reserva inválido"}), 400

    reserva = reservas_col.find_one({"_id": reserva_obj_id, "usuario_id": user_id})
    if not reserva:
        return jsonify({"success": False, "error": "Reserva no encontrada"}), 404

    if reserva.get("estado") == "CANCELADO":
        return jsonify({"success": False, "error": "La reserva ya ha sido cancelada previamente"}), 400

    reservas_col.update_one({"_id": reserva_obj_id},
                            {"$set": {"estado": "CANCELADO", "cancelado_en": datetime.utcnow()}})

    asientos_liberados = reserva.get("asientos", [])
    cant_liberada = len(asientos_liberados)
    vuelo_id = reserva.get("vuelo_id")

    if vuelo_id:
        vuelos_col.update_one(
            {"_id": vuelo_id},
            {
                "$pullAll": {"asientos_ocupados": asientos_liberados},
                "$inc": {"asientos_disponibles": cant_liberada}
            }
        )

    correo_destino = session.get("correo")
    if not correo_destino and reserva.get("detalles_pasajeros"):
        correo_destino = reserva["detalles_pasajeros"][0].get("correo")

    if correo_destino:
        html_cancelacion = f"""
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: #dc2626; margin-bottom: 5px;">Cancelación de Boleto Confirmada</h2>
                <p style="color: #64748b; font-size: 13px;">El trámite de tu boleto ha sido cancelado exitosamente</p>
            </div>

            <div style="background-color: #fef2f2; padding: 18px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #fee2e2;">
                <p style="margin: 6px 0;"><b>Código de Confirmación:</b> <span style="background-color: #fecaca; color: #991b1b; padding: 3px 8px; border-radius: 6px; font-weight: bold;">{reserva.get('codigo_confirmacion')}</span></p>
                <p style="margin: 6px 0;"><b>Ruta:</b> {reserva.get('origen')} &rarr; {reserva.get('destino')}</p>
                <p style="margin: 6px 0;"><b>Asientos Liberados:</b> {', '.join(asientos_liberados)}</p>
                <p style="margin: 6px 0;"><b>Monto del Reembolso en Proceso:</b> ${reserva.get('total', 0):,.1f} MXN</p>
            </div>
            <p style="font-size: 12px; color: #64748b;">Los asientos asignados han sido reactivados en la plataforma.</p>
            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">
            <p style="font-size: 11px; color: #94a3b8; text-align: center;">NextStop &bull; Soporte y Gestión de Boletos.</p>
        </div>
        """
        enviar_correo_notificacion(correo_destino, f"Boleto Cancelado - {reserva.get('codigo_confirmacion')}",
                                   html_cancelacion)

    return jsonify({"success": True, "mensaje": "Reserva cancelada correctamente"})


# --- PROCESO DE RESERVA Y CONFIRMACIÓN ---

@app.route("/reservar/<vuelo_id>")
@login_requerido("cliente")
def reservar_paso1(vuelo_id):
    try:
        vuelo = vuelos_col.find_one({"_id": ObjectId(vuelo_id)})
    except Exception:
        abort(404)
    if not vuelo:
        abort(404)

    asientos_ocupados = vuelo.get("asientos_ocupados")
    if asientos_ocupados is None:
        disp = vuelo.get("asientos_disponibles", 35)
        cant_oc = max(0, len(TODOS_LOS_ASIENTOS) - disp)
        asientos_ocupados = random.sample(TODOS_LOS_ASIENTOS, cant_oc)
        vuelos_col.update_one({"_id": vuelo["_id"]}, {"$set": {"asientos_ocupados": asientos_ocupados}})

    return render_template("reservas/reservarAsiento.html", vuelo=vuelo, asientos_ocupados=asientos_ocupados,
                           nombre=session.get("nombre"))


@app.route("/reservar/<vuelo_id>/pasajeros", methods=["POST"])
@login_requerido("cliente")
def reservar_paso2(vuelo_id):
    try:
        vuelo = vuelos_col.find_one({"_id": ObjectId(vuelo_id)})
    except Exception:
        abort(404)

    asientos_str = request.form.get("asiento_seleccionado", "")
    asientos_lista = [s.strip() for s in asientos_str.split(",") if s.strip()]

    if not asientos_lista:
        flash("Debes seleccionar al menos un asiento para continuar.", "error")
        return redirect(url_for("reservar_paso1", vuelo_id=vuelo_id))

    pasajeros_cant = int(request.form.get("cantidad_pasajeros", len(asientos_lista)))

    cargo_premium_total = 0.0
    for seat in asientos_lista:
        fila = int(seat[:-1]) if seat[:-1].isdigit() else 1
        if fila <= 5:
            cargo_premium_total += 2500.0

    total = (vuelo["precio"] * pasajeros_cant) + cargo_premium_total
    usuario = usuarios_col.find_one({"_id": ObjectId(session["user_id"])})

    return render_template(
        "reservas/reservarPasajero.html",
        vuelo=vuelo,
        asientos=asientos_lista,
        asientos_str=",".join(asientos_lista),
        pasajeros=pasajeros_cant,
        cargo_premium=cargo_premium_total,
        total=total,
        usuario=usuario,
        nombre=session.get("nombre")
    )


@app.route("/reservar/<vuelo_id>/confirmar", methods=["POST"])
@login_requerido("cliente")
def reservar_paso3(vuelo_id):
    try:
        vuelo = vuelos_col.find_one({"_id": ObjectId(vuelo_id)})
    except Exception:
        abort(404)

    asientos_str = request.form.get("asientos", "")
    asientos_lista = [s.strip() for s in asientos_str.split(",") if s.strip()]
    pasajeros_cant = int(request.form.get("pasajeros", len(asientos_lista) or 1))
    total = float(request.form.get("total", vuelo["precio"]))

    pasajeros_detalles = []
    for i in range(1, pasajeros_cant + 1):
        nombre_pax = request.form.get(f"nombre_{i}", "").strip()
        apellidos_pax = request.form.get(f"apellidos_{i}", "").strip()
        prefijo_pax = request.form.get(f"prefijo_{i}", "+52")
        tel_pax = request.form.get(f"telefono_{i}", "").strip()
        correo_pax = request.form.get(f"correo_{i}", "").strip()
        nacimiento_pax = request.form.get(f"fecha_nacimiento_{i}", "")
        nacionalidad_pax = request.form.get(f"nacionalidad_{i}", "Mexicana")
        tipo_id_pax = request.form.get(f"tipo_id_{i}", "")

        if not re.match(PATRON_TELEFONO, tel_pax):
            flash(f"El teléfono del Pasajero {i} debe tener el formato XXX-XXX-XXXX con 10 dígitos.", "error")
            return redirect(url_for("reservar_paso1", vuelo_id=vuelo_id))

        pasajeros_detalles.append({
            "numero_viajero": i,
            "asiento": asientos_lista[i - 1] if i - 1 < len(asientos_lista) else "Asignado",
            "nombre_completo": f"{nombre_pax} {apellidos_pax}".strip(),
            "correo": correo_pax,
            "telefono": f"{prefijo_pax} {tel_pax}",
            "fecha_nacimiento": nacimiento_pax,
            "nacionalidad": nacionalidad_pax,
            "tipo_id": tipo_id_pax
        })

    codigo_confirmacion = f"NS-{uuid.uuid4().hex[:6].upper()}"

    reserva_doc = {
        "codigo_confirmacion": codigo_confirmacion,
        "usuario_id": ObjectId(session["user_id"]),
        "vuelo_id": vuelo["_id"],
        "aerolinea_codigo": vuelo.get("aerolinea_codigo", "AM"),
        "aerolinea_nombre": vuelo.get("aerolinea_nombre", "Aerolínea"),
        "origen": vuelo["codigo_origen"],
        "destino": vuelo["codigo_destino"],
        "origen_nombre": vuelo.get("origen"),
        "destino_nombre": vuelo.get("destino"),
        "fecha": vuelo["fecha"],
        "hora_salida": vuelo.get("hora_salida"),
        "pasajeros": pasajeros_cant,
        "asientos": asientos_lista,
        "total": total,
        "detalles_pasajeros": pasajeros_detalles,
        "estado": "CONFIRMADO",
        "creado_en": datetime.utcnow()
    }
    reservas_col.insert_one(reserva_doc)

    vuelos_col.update_one(
        {"_id": vuelo["_id"]},
        {
            "$addToSet": {"asientos_ocupados": {"$each": asientos_lista}},
            "$inc": {"asientos_disponibles": -pasajeros_cant}
        }
    )

    html_pax = "".join([
        f"<li><b>{p['nombre_completo']}</b> &mdash; Asiento: <span style='color:#047857; font-weight:bold;'>{p['asiento']}</span> (Doc: {p['tipo_id']})</li>"
        for p in pasajeros_detalles
    ])

    html_compra = f"""
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 16px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="color: #047857; margin-bottom: 5px;">¡Vuelo Confirmado con Éxito!</h2>
            <p style="color: #64748b; font-size: 13px;">Tu pago ha sido procesado de manera segura</p>
        </div>

        <div style="background-color: #f8fafc; padding: 18px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
            <p style="margin: 6px 0;"><b>Ruta:</b> {vuelo['codigo_origen']} &rarr; {vuelo['codigo_destino']}</p>
            <p style="margin: 6px 0;"><b>Fecha del Vuelo:</b> {vuelo['fecha']}</p>
            <p style="margin: 6px 0;"><b>Código de Confirmación:</b> <span style="background-color: #ffedd5; color: #c2410c; padding: 3px 8px; border-radius: 6px; font-weight: bold;">{codigo_confirmacion}</span></p>
            <p style="margin: 6px 0;"><b>Monto Total Pagado:</b> ${total:,.1f} MXN</p>
        </div>

        <h4 style="color: #1e293b; margin-bottom: 8px;">Detalle de Pasajeros y Asientos:</h4>
        <ul style="color: #475569; font-size: 13px; line-height: 1.8;">
            {html_pax}
        </ul>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">
        <p style="font-size: 11px; color: #94a3b8; text-align: center;">NextStop &bull; Plataforma de Reservaciones de Vuelos con Cifrado en Tránsito TLS.</p>
    </div>
    """

    destinatarios_compra = set()
    if session.get("correo"):
        destinatarios_compra.add(session.get("correo").strip().lower())

    for p in pasajeros_detalles:
        if p.get("correo"):
            destinatarios_compra.add(p["correo"].strip().lower())

    for email_destino in destinatarios_compra:
        enviar_correo_notificacion(email_destino, f"Confirmación de Vuelo - {codigo_confirmacion}", html_compra)

    return render_template(
        "reservas/reservarConfirmacion.html",
        vuelo=vuelo,
        reserva=reserva_doc,
        nombre=session.get("nombre")
    )


@app.route("/dashboard/proveedor")
@login_requerido("proveedor")
def dashboardProveedor():
    return render_template("proveedor/dashboardProveedor.html", nombre=session.get("nombre"))


if __name__ == "__main__":
    try:
        client.admin.command('ping')
        print("✓ Conexión establecida exitosamente con MongoDB Atlas.")
    except Exception as error:
        print(f"✗ Error al conectar con MongoDB Atlas: {error}")

    app.run(debug=True, port=5000)