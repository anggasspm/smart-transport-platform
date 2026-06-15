import kaggle
import zipfile
import os

MODEL_1_URL = "rasyidstat/transjakarta-bus-gps-data"

def download_dataset(dataset_url):
    print(f'Downloading dataset {dataset_url}')
    kaggle.api.authenticate()
    kaggle.api.dataset_download_files(dataset_url, unzip=True)
    print("Dataset downloaded successfully!")

download_dataset(MODEL_1_URL)
