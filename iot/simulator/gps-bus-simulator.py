import json
import random
import time

import paho.mqtt.client as mqtt

BROKER = "broker.hivemq.com"
PORT = 1883

client = mqtt.Client(client_id="GPS_SIMULATOR")
client.connect(BROKER, PORT)

# titik latitude tiap rute
route_lat = [
    -6.3112,
    -6.3115,
    -6.3120,
    -6.3125,
    -6.3130,
]

# titik longtitude
route_lng = [
    106.8135,
    106.8140,
    106.8147,
    106.8152,
    106.8160,
]

total_bus = 30

status_bus = {}

for bus_id in range(1, total_bus + 1):
    status_bus[bus_id] = {
        "current_halte_ke": 0,
        "route_id": 1
    }

while True:

    for bus_id in range(1, total_bus + 1):

        state = status_bus[bus_id]

        # posisi bis sekarang di rute ke (indeks)
        current_halte_ke = state["current_halte_ke"]

        gps_data = {
            "bus_id": bus_id,
            "lat": route_lat[current_halte_ke],
            "lng": route_lng[current_halte_ke],
            "speed_kmh": random.randint(20, 50),
            "heading": random.randint(0, 360),
            "route_id": state["route_id"],
            "timestamp": int(time.time())
        }

        topic_gps = f"city/bus/{bus_id}/gps"

        client.publish(
            topic_gps,
            json.dumps(gps_data)
        )

        # info di terminal
        print(
            f"[gps-simulator] Bus {bus_id}"
            f"Rute Ke (indeks) = {current_halte_ke}"
            f"lat = {gps_data['lat']} lng = {gps_data['lng']}"
            f"speed = {gps_data['speed_kmh']} km/h"
        )

        # gerak ke rute selanjutnya
        state["current_halte_ke"] += 1

        if state["current_halte_ke"] >= len(route_lat):
            state["current_halte_ke"] = 0
    
    # kirim tiap 30 detik
    time.sleep(30)