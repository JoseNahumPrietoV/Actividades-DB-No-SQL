import os
from flask import Flask, render_template, abort
from pymongo import MongoClient
from dotenv import load_dotenv

# Cargar variables del entorno desde .env
load_dotenv()

app = Flask(__name__)
app.secret_key = os.getenv("SECRET_KEY", "dev_secret_key")

# Conexión a MongoDB Atlas
MONGO_URI = os.getenv("MONGO_URI")
client = MongoClient(MONGO_URI)
db = client["nextstop"]
usuarios_col = db["usuarios"]

@app.route("/")
def index():
    return render_template("index.html")

@app.route("/login/<tipo>")
def login(tipo):
    if tipo not in ["proveedor", "cliente"]:
        abort(404)
    return f"<h2>Módulo de inicio de sesión para: {tipo.capitalize()} (En construcción)</h2>"

if __name__ == "__main__":
    try:
        # Prueba ping para confirmar enlace con Atlas
        client.admin.command('ping')
        print("✓ Conexión establecida exitosamente con MongoDB Atlas.")
    except Exception as error:
        print(f"✗ Error al conectar con MongoDB Atlas: {error}")

    app.run(debug=True, port=5000)