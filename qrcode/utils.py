import random
import string
import qrcode
import mysql.connector

"""
QR code functions
"""
def get_random_string(length=10):
    return ''.join([random.choice(string.ascii_letters) for _ in range(length)])


def generate_qrcode(text, path='./qrcode.png'):
    img = qrcode.make(text)
    img.save(path)
    print(f"[STORE QRCODE] - {path}")
    return img


"""
MySQL functions
"""
def create_mysql_connection(host_name, user_name, user_password, db_name=None):
    cnx = None
    try:
        cnx = mysql.connector.connect(
            host=host_name,
            user=user_name,
            password=user_password,
            database=db_name
        )
        print("MySQL Database connection successful")
    except mysql.connector.Error as err:
        print(f"Error: '{err}'")
    return cnx


def execute_query(cnx, query):
    cursor = cnx.cursor()
    try:
        cursor.execute(query)
        cnx.commit()
        print("Query successful")
    except mysql.connector.Error as err:
        print(f"Error: '{err}'")
    finally:
        cursor.close()


def read_query(cnx, query):
    cursor = cnx.cursor()
    result = None
    try:
        cursor.execute(query)
        result = cursor.fetchall()
        cnx.commit()
    except mysql.connector.Error as err:
        print(f"Error: '{err}'")
    finally:
        cursor.close()
    return result
