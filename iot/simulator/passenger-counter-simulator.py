import json
import random
import time
import paho.mqtt.client as mqtt

BROKER = "broker.hivemq.com"
PORT = 1883

KAPASITAS_BUS = 40

STOP_IDS = [1, 2, 3, 4, 5]

total_bus = 30

client = mqtt.Client(client_id="PASSENGER_SIMULATOR")
client.connect(BROKER, PORT)

status_bus = {}

for bus_id in range(1, total_bus + 1):
    status_bus[bus_id] = {
        "current_halte_ke": 0,
        "boarded": 0,
        "alighted": 0,
        "current_load": random.randint(5, 20)
    }

while True:

    for bus_id in range(1, total_bus + 1):

        state = status_bus[bus_id]
        current_halte_ke = state["current_halte_ke"]
        stop_id = STOP_IDS[current_halte_ke]

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
            f"[passenger-counter-simulator] Bus {bus_id}"
            f"Stop Id = {stop_id}"
            f"Load = {state['current_load']}/{KAPASITAS_BUS}"
            f"Event = {event}"
        )

        state["current_halte_ke"] += 1

        if state["current_halte_ke"] >= len(STOP_IDS):
            state["current_halte_ke"] = 0

    time.sleep(30)