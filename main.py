import requests
import time
import pymysql
import logging
import yaml
from pymysql.cursors import DictCursor

logger = logging.getLogger('device_listen')


def device_listen():
    try:
        with open('device_listen.conf', "r") as f:  # загружаем настройки
            conf = yaml.safe_load(f)

        # настраиваем логгинг
        logger.setLevel(logging.DEBUG)
        ch = logging.StreamHandler()
        hf = logging.FileHandler(conf['app']['log_file'])
        hf.setLevel(logging.DEBUG)
        ch.setLevel(logging.DEBUG)
        formatter = logging.Formatter('%(asctime)s [%(levelname)s] [%(process)s] %(message)s', '%H:%M:%S')
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
                # загружаем список устройств
                query = "SELECT id_device, address FROM devices"
                with connection.cursor() as cur_select:
                    cur_select.execute(query)
                for row in cur_select.fetchall():   # по каждому устройству
                    response = requests.get('http://%s/all' % (row['address']))  # получаем данные с устройства
                    logger.info(response.text)

                    # записываем данные в таблицу info
                    query = "INSERT INTO info (date, id_device, json) VALUES(CURRENT_TIMESTAMP, %s, '%s')" \
                            % (row['id_device'], response.text)
                    with connection.cursor() as cur_insert:
                        cur_insert.execute(query)

                    # загружаем перечень датчиков устройства
                    data = response.json()
                    query = "SELECT id_sensor, name FROM sensor WHERE id_device=1"
                    with connection.cursor() as cur_sens:
                        cur_sens.execute(query)
                    for row_sens in cur_sens.fetchall():    # по каждому датчику
                        # записываем данные в таблицу data
                        d = data[row_sens['name']]
                        if isinstance(d, (int, float)): # если данные корректные
                            query = "INSERT INTO sensor_data (date, id_sensor, data_float) VALUES(CURRENT_TIMESTAMP, %s, IFNULL(%s,0))" \
                                    % (row_sens['id_sensor'], d)
                            with connection.cursor() as cur_insert:
                                cur_insert.execute(query)
                        else:
                            logger.error("Wrong data: " + str(d))
                    connection.commit()
                    time.sleep(conf['app']['delay'])

    except pymysql.Error as e:
        print(e)
        logger.error('Connection error: ' + format(e))


def main():
    # запускаем сервер
    device_listen()


main()
