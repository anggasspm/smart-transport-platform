import json
import random
import time
import paho.mqtt.client as mqtt

BROKER = "mosquitto"
PORT = 1883

client = mqtt.Client(client_id="GPS_SIMULATOR")
client.connect(BROKER, PORT)

total_bus = 30
TOTAL_RUTE = 10
BUS_PER_RUTE = 3 

# titik latitude tiap rute
route_lat = {
    1: [-6.3112, -6.3115, -6.3120, -6.3125, -6.3130],
    2: [-6.3140, -6.3145, -6.3150, -6.3155, -6.3160],
    3: [-6.3170, -6.3175, -6.3180, -6.3185, -6.3190],
    4: [-6.3200, -6.3205, -6.3210, -6.3215, -6.3220],
    5: [-6.3230, -6.3235, -6.3240, -6.3245, -6.3250],
    6: [-6.3260, -6.3265, -6.3270, -6.3275, -6.3280],
    7: [-6.3290, -6.3295, -6.3300, -6.3305, -6.3310],
    8: [-6.3320, -6.3325, -6.3330, -6.3335, -6.3340],
    9: [-6.3350, -6.3355, -6.3360, -6.3365, -6.3370],
    10: [-6.3380, -6.3385, -6.3390, -6.3395, -6.3400],
}

# titik longtitude
route_lng = {
    1: [106.8135, 106.8140, 106.8147, 106.8152, 106.8160],
    2: [106.8165, 106.8170, 106.8177, 106.8182, 106.8190],
    3: [106.8195, 106.8200, 106.8207, 106.8212, 106.8220],
    4: [106.8225, 106.8230, 106.8237, 106.8242, 106.8250],
    5: [106.8255, 106.8260, 106.8267, 106.8272, 106.8280],
    6: [106.8285, 106.8290, 106.8297, 106.8302, 106.8310],
    7: [106.8315, 106.8320, 106.8327, 106.8332, 106.8340],
    8: [106.8345, 106.8350, 106.8357, 106.8362, 106.8370],
    9: [106.8375, 106.8380, 106.8387, 106.8392, 106.8400],
    10: [106.8405, 106.8410, 106.8417, 106.8422, 106.8430],
}

status_bus = {}

for bus_id in range(1, total_bus + 1):

    route_id = ((bus_id - 1) // BUS_PER_RUTE) + 1

    posisi_dalam_rute = (bus_id - 1) % BUS_PER_RUTE
    
    delay_start = posisi_dalam_rute * 3

    status_bus[bus_id] = {
        "route_id": route_id,
        "current_halte_ke": 0,
        "arah": 1,
        "delay_start": delay_start
    }

while True:

    for bus_id in range(1, total_bus + 1):

        state = status_bus[bus_id]
        route_id = state["route_id"]

        # posisi bis sekarang di rute ke (indeks)
        current_halte_ke = state["current_halte_ke"]

        gps_data = {
            "bus_id": bus_id,
            "lat": route_lat[route_id][current_halte_ke],
            "lng": route_lng[route_id][current_halte_ke],
            "speed_kmh": random.randint(20, 50),
            "heading": random.randint(0, 360),
            "route_id": route_id,
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
            f"Route {route_id}"
            f"HalteKe (indeks) = {current_halte_ke}"
            f"lat = {gps_data['lat']} lng = {gps_data['lng']}"
            f"speed = {gps_data['speed_kmh']} km/h"
        )

        if state["delay_start"] > 0:

            state["delay_start"] -= 1
        else:
            
            last_index = len(route_lat[route_id]) - 1

            if current_halte_ke <= 0:
                state["arah"] = 1
            elif current_halte_ke >= last_index:
                state["arah"] = -1
            
            # gerak ke rute selanjutnya
            state["current_halte_ke"] += state["arah"]

    # kirim tiap 30 detik
    time.sleep(30)