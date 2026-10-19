import os
import random
from datetime import datetime, timedelta
from pymongo import MongoClient
from dotenv import load_dotenv

load_dotenv()

client = MongoClient(os.getenv("MONGO_URI"))
db = client["NextStop"]
vuelos_col = db["vuelos"]


def actualizar_fechas():
    vuelos = list(vuelos_col.find())
    print(f"Total de vuelos encontrados en Atlas: {len(vuelos)}")

    hoy = datetime.utcnow()
    actualizados = 0

    for vuelo in vuelos:
        # Genera una fecha entre 1 y 90 días a partir de hoy
        dias_futuro = random.randint(1, 90)
        nueva_fecha = (hoy + timedelta(days=dias_futuro)).strftime("%Y-%m-%d")

        vuelos_col.update_one(
            {"_id": vuelo["_id"]},
            {"$set": {"fecha": nueva_fecha}}
        )
        actualizados += 1

    print(f"✓ Se actualizaron exitosamente {actualizados} vuelos con fechas vigentes a futuro.")


if __name__ == "__main__":
    actualizar_fechas()