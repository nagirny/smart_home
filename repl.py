from requests.exceptions import *
import requests
import pymysql
import logging
import time
import yaml
from pymysql.cursors import DictCursor

logger = logging.getLogger('repl')


def repl():
    try:
        with open('repl.conf', "r") as f:  # загружаем настройки
            conf = yaml.safe_load(f)

        # настраиваем логгинг. Пишем и в файл и в консоль
        logger.setLevel(logging.DEBUG)
        ch = logging.StreamHandler()
        hf = logging.FileHandler(conf['log_file'])
        hf.setLevel(logging.DEBUG)
        ch.setLevel(logging.DEBUG)
        formatter = logging.Formatter('%(asctime)s [%(levelname)s] [%(process)s] %(message)s', '%d.%m %H:%M:%S')
        hf.setFormatter(formatter)
        ch.setFormatter(formatter)
        logger.addHandler(ch)
        logger.addHandler(hf)
        logger.info('Run')

        with pymysql.connect(
            host=conf['db']['host'],
            user=conf['db']['user'],
            password=conf['db']['pass'],
            database=conf['db']['name'],
            cursorclass=DictCursor
        ) as connection:
            while True:
                # загружаем нереплецированные строки данных
                query = "SELECT date, data_float, id_sensor FROM sensor_data WHERE rep=false FOR UPDATE"
                with connection.cursor() as cur_select:
                    # если есть что реплицировать
                    if cur_select.execute(query):
                        for row in cur_select.fetchall():
                            try:
                                response = requests.post(conf['host_name'], data=row)  # отправляю данные для записи в базу
                            except all():
                                logger.error('Host connection error: ')
                                return 1
                            logger.info(format(response)+", date: "+format(row['date']))
                        # отмечаем строки реплицированными
                        query = "UPDATE sensor_data SET rep=true WHERE rep=false"
                        with connection.cursor() as cur_upd:
                            cur_upd.execute(query)
                connection.commit()
                time.sleep(conf['delay'])

    except pymysql.Error as e:
        logger.error('Connection error: ' + format(e))
        print(e)
        return 1


def main():
    # запускаем сервер
    while repl():
        logger.info('Restart function')
        time.sleep(conf['app']['delay'])


main()
