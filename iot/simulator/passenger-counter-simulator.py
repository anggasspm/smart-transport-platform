import json
import random
import time
import paho.mqtt.client as mqtt

BROKER = "broker.hivemq.com"
PORT = 1883

KAPASITAS_BUS = 40
total_bus = 30
TOTAL_RUTE = 10
BUS_PER_RUTE = 3
STOP_PER_RUTE = 5

client = mqtt.Client(client_id="PASSENGER_SIMULATOR")
client.connect(BROKER, PORT)

status_bus = {}

for bus_id in range(1, total_bus + 1):

    route_id = ((bus_id - 1) // BUS_PER_RUTE) + 1

    posisi_dalam_rute = (bus_id - 1) % BUS_PER_RUTE
    
    delay_start = posisi_dalam_rute * 3

    status_bus[bus_id] = {
        "route_id": route_id,
        "current_halte_ke": 0,
        "arah": 1,
        "boarded": 0,
        "alighted": 0,
        "current_load": random.randint(5, 20)
    }

while True:

    for bus_id in range(1, total_bus + 1):

        state = status_bus[bus_id]
        route_id = state["route_id"]
        current_halte_ke = state["current_halte_ke"]
        stop_id = (route_id - 1) * STOP_PER_RUTE + (current_halte_ke + 1)

        event = random.choice(
            [
                "board",
                "board",
                "board_alight",
                "alight",
                "none"
            ]
        )

        boarded_now = random.randint(1, 5)
        alighted_now = random.randint(1, 5)

        # jika ada yang naik
        if event == "board" and state["current_load"] < KAPASITAS_BUS:

            state["boarded"] += boarded_now
            state["current_load"] += boarded_now

        # jika turun
        elif event == "alight" and state["current_load"] > 0:

            state["alighted"] += alighted_now
            state["current_load"] -= alighted_now

        # jika ada yang naik dan turun
        elif event == "board_alight" and state["current_load"] + boarded_now <= KAPASITAS_BUS and state["current_load"] >= alighted_now:

            state["boarded"] += boarded_now
            state["alighted"] += alighted_now
            state["current_load"] = state["current_load"] + boarded_now - alighted_now

        passenger_data = {
            "bus_id": bus_id,
            "stop_id": stop_id,
            "boarded": state["boarded"],
            "alighted": state["alighted"],
            "current_load": state["current_load"],
            "capacity": KAPASITAS_BUS,
            "timestamp": int(time.time())
        }

        topic_passenger = f"city/bus/{bus_id}/passengers"

        client.publish(
            topic_passenger,
            json.dumps(passenger_data)
        )

        # info di terminal
        print(
            f"[passenger-counter-simulator] Bus {bus_id} "
            f"Route {route_id} "
            f"Stop Id = {stop_id} "
            f"Load = {state['current_load']}/{KAPASITAS_BUS} "
            f"Event = {event}"
        )

        if state["delay_start"] > 0:

            state["delay_start"] -= 1
        else:
            
            last_index = STOP_PER_RUTE - 1

            if current_halte_ke <= 0:
                state["arah"] = 1
            elif current_halte_ke >= last_index:
                state["arah"] = -1

            state["current_halte_ke"] += state["arah"]

    time.sleep(30)