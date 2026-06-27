#!/bin/bash
set -e

MODEL_DIR="models"
mkdir -p $MODEL_DIR

echo "Downloading ML models..."

gdown "1dRXjoN5l-Y2fDwebfiufapjKWf1kEGIc" -O $MODEL_DIR/model1_delay_regressor_v2.pkl
gdown "1qSv2J2FHCsS-QAc_bk6pqq-6AGnKvEzw" -O $MODEL_DIR/model1_corridor_encoder_v2.pkl
gdown "1600LWfZ_58qJkNnE66pHaXbmI4QcwiIN" -O $MODEL_DIR/model1_config_v2.json
gdown "1a7ygkYeJsJ03yGpUH_VT-IeHkWP3RC25" -O $MODEL_DIR/model2_crowd_classifier.pkl
gdown "1VOy0EYhTNMN1X5gTBRhdIWJihm2TvmMa" -O $MODEL_DIR/model2_target_mapping.pkl
gdown "1kT8ApbumnX3sBllPLjpJJzpH5vbhnMkY" -O $MODEL_DIR/model2_feature_order.pkl
gdown "1vS40ttdwrAuUb96shwiTL9Hn-OMv_Ox_" -O $MODEL_DIR/model3_anomaly_detector_v2.pkl
gdown "1Ft1dcBpJ46fpWeGtHNHbpgyCQqnkzVCo" -O $MODEL_DIR/model3_config_v2.json

echo "All models downloaded."