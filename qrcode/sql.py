import mysql.connector


def create_mysql_connection(host_name, user_name, user_password, db_name=None):
    connection = None
    try:
        connection = mysql.connector.connect(
            host=host_name,
            user=user_name,
            password=user_password,
            database=db_name
        )
        print("MySQL Database connection successful")
    except mysql.connector.Error as err:
        print(f"Error: '{err}'")
    return connection


def execute_query(connection, query):
    cursor = connection.cursor()
    try:
        cursor.execute(query)
        connection.commit()
        print("Query successful")
    except mysql.connector.Error as err:
        print(f"Error: '{err}'")


def read_query(connection, query):
    cursor = connection.cursor()
    result = None
    try:
        cursor.execute(query)
        result = cursor.fetchall()
    except Error as err:
        print(f"Error: '{err}'")
    return result

# MySQL commands
# CREATE DATABASE IF NOT EXISTS {dbname};
# INSERT INTO {tablename} VALUES (v, v), (v, v);
# DELETE FROM {tablename} WHERE {conditions}; e.g. attribute=value

def create_guest_group(connection):
    # create guest group
    # TODO: what are Auth-Type and Service-Type?
    query = """
        INSERT INTO radgroupreply (groupname, attribute, op, value)
        VALUES ('guest', 'Auth-Type', ':=', 'CHAP');
        Insert INTO radgroupreply (groupname, attribute, op, value)
        VALUES ('guest', 'Service-Type', ':=', 'Framed-User');
    """
    execute(connection, query)


def create_guest_account(connection, user_name, user_password):
    # insert user_name and user_password into radcheck table
    # register and set limit
    query1 = f"""
        INSERT INTO radcheck (username, attribute, op, value)
        VALUES ('{user_name}', 'Cleartext-Password', ':=', '{user_password}');
        INSERT INTO radcheck (username, attribute, op, value)
        VALUES ('{username}', 'Max-Daily-Session', ':=', 300);
        INSERT INTO radcheck (username, attribute, op, value)
        VALUES ('{username}', 'Max-Daily-Traffic', ':=', 10485760);";
    """
    query2 = f"""
        INSERT INTO radusergroup (username, groupname)
        VALUES ('{user_name}', 'guest');
    """
    execute_query(connection, query1)
    execute_query(connection, query2)


def delete_guest_account(connection, user_name):
    # delete user_name
    query = f"""
        DELETE FROM radcheck WHERE username={user_name};
        DELETE FROM radusergroup WHERE username={user_name};
    """
    execute_query(connection, query)


connection = create_mysql_connection("127.0.0.1", "radius", "123456", "radius")
