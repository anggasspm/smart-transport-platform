import logging
import os
import time

import pika

logger = logging.getLogger(__name__)

RABBITMQ_USER = os.getenv("RABBITMQ_USER", "guest")
RABBITMQ_PASS = os.getenv("RABBITMQ_PASS", "guest")


def connect_with_retry(host, max_retries=10, delay_seconds=5):
    credentials = pika.PlainCredentials(RABBITMQ_USER, RABBITMQ_PASS)

    for attempt in range(1, max_retries + 1):
        try:
            connection = pika.BlockingConnection(
                pika.ConnectionParameters(host=host, credentials=credentials)
            )
            logger.info(f"Connected to RabbitMQ on attempt {attempt}")
            return connection
        except pika.exceptions.AMQPConnectionError as e:
            logger.warning(f"RabbitMQ not ready (attempt {attempt}/{max_retries}): {e}")
            if attempt == max_retries:
                logger.error("Max retries reached. Could not connect to RabbitMQ.")
                raise
            time.sleep(delay_seconds)