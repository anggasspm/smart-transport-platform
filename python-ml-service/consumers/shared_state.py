# Shared state module untuk simpen speed_history per bus_id.
# Dipakai di:
# consumer.py: append data baru tiap kali gps.update masuk (Model 3 rolling features)
# model1_service.py: baca histori untuk speed_lag_1/2/rolling_mean_3 (Model 1 baru)

# PENTING: karena ini in-memory state, hilang kalo container restart. 
from collections import deque, defaultdict

SPEED_WINDOW_SIZE = 120  # ~1 jam kalau GPS update tiap 30 detik

speed_history = defaultdict(lambda: deque(maxlen=SPEED_WINDOW_SIZE))

def append_speed(bus_id, speed: float):
    speed_history[bus_id].append(speed)

def get_last_n_speeds(bus_id, n: int):
    history = speed_history.get(bus_id)
    if not history:
        return []
    return list(history)[-n:]