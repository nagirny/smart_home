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
                # загружаем список невыполненных команд
                query = "SELECT devices.address addr, controls.name name, command, id_command "\
                        "FROM commands, controls, devices " \
                        "WHERE commands.id_control=controls.id_control AND controls.id_device=devices.id_device " \
                        "AND commands.run=0 FOR UPDATE"
                with connection.cursor() as cur_select:
                    cur_select.execute(query)
                for row in cur_select.fetchall():   # по каждому устройству
                    try:
                        # отправляем команду на устройство
                        response = requests.get('http://%s/act?%s=%s' % (row['addr'], row['name'], row['command']))
                        logger.info(response.text)
                        # если комманда отправлена
                        if response.status_code == 200:
                            # отмечаем в базе исполнение
                            query = "UPDATE commands SET run=1, date_run=NOW() WHERE id_command=%s" % (row['id_command'])
                            with connection.cursor() as cur_update:
                                cur_update.execute(query)
                    except all:
                        logger.error("Can't connect to device to send command")
                        time.sleep(conf['app']['delay'])
                        return 1
                connection.commit()

                # загружаем список устройств
                query = "SELECT id_device, address FROM devices"
                with connection.cursor() as cur_select:
                    cur_select.execute(query)
                for row in cur_select.fetchall():   # по каждому устройству
                    try:
                        response = requests.get('http://%s/all' % (row['address']))  # получаем данные с устройства
                        logger.info(response.text)
                    except all:
                        logger.error("Can't connect to device")
                        time.sleep(conf['app']['delay'])
                        return 1

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
        return 0

    except pymysql.Error as e:
        print(e)
        logger.error('Connection error: ' + format(e))
        time.sleep(conf['app']['delay'])
        return 1


def main():
    # запускаем сервер
    while device_listen():
        logger.info("Restart function")


main()
