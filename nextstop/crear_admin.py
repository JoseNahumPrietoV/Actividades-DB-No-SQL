import os
from pymongo import MongoClient
from werkzeug.security import generate_password_hash
from dotenv import load_dotenv
from datetime import datetime

load_dotenv()

client = MongoClient(os.getenv("MONGO_URI"))
db = client["NextStop"]
usuarios_col = db["usuarios"]

admin_doc = {
    "nombre": "Jose ignacio vallejo zuñiga",
    "correo": "admin123@gmail.com",
    "password_hash": generate_password_hash("Jivz2004"),
    "rol": "admin",
    "creado_en": datetime.utcnow()
}

# Evitar duplicados
if not usuarios_col.find_one({"correo": "admin123@gmail.com"}):
    usuarios_col.insert_one(admin_doc)
    print("Administrador creado exitosamente en MongoDB.")
else:
    print("El administrador ya existe.")