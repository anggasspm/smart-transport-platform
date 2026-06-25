#!/bin/bash
set -e

MODEL_DIR="models"
mkdir -p $MODEL_DIR

echo "Downloading ML models..."

gdown "1fXjUHRt4nMDVvPN-7txPDZDs4nsbe9SU" -O $MODEL_DIR/model1_delay_regressor.pkl
gdown "11Tim9EpymUctMMPhy4eo8oNvjhg2Rmeo" -O $MODEL_DIR/model1_corridor_encoder.pkl
gdown "1pFxeFEElNTVNeyf2ewRnqSQ1zkXZexkE" -O $MODEL_DIR/model1_config_v2.json
gdown "1a7ygkYeJsJ03yGpUH_VT-IeHkWP3RC25" -O $MODEL_DIR/model2_crowd_classifier.pkl
gdown "1VOy0EYhTNMN1X5gTBRhdIWJihm2TvmMa" -O $MODEL_DIR/model2_target_mapping.pkl
gdown "1kT8ApbumnX3sBllPLjpJJzpH5vbhnMkY" -O $MODEL_DIR/model2_feature_order.pkl
gdown "1WqZ3TJOCkCWGpEmTerSuf80XRH1Ugy1e" -O $MODEL_DIR/model3_anomaly_detector.pkl
gdown "1Zt05hNtu2-LC73LQ6Y8WopDCE00ecIQj" -O $MODEL_DIR/model3_config.json
gdown "12oo5hIWDLnlxBNDDHimGxGY74q9XhWjE" -O $MODEL_DIR/model3_scaler.pkl


echo "All models downloaded."