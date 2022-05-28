from http.server import BaseHTTPRequestHandler, HTTPServer
import time
import utils
import const
import datetime


class HandleServer(BaseHTTPRequestHandler):
    cnx = utils.create_mysql_connection(const.SQL_HOST,
            const.ROOT_ACCOUNT, const.ROOT_PASSWORD, const.DATABASE)
    # new guest account (and same password)
    account = None

    def __init__(self, *args, **kwargs):
        super(HandleServer, self).__init__(*args, **kwargs)

    def do_GET(self):
        print(f"[PATH] - {self.path}")
        if self.path == '/':
            self._handle_root()
        elif self.path.startswith('/qrcode'):
            self._handle_qrcode()
        elif self.path.startswith('/favicon'):
            self._handle_favicon()

    def _handle_root(self):
        # do not change this
        HandleServer._remove_expired_account()
        if HandleServer._guest_account_is_used() or not HandleServer._guest_account_exist():
            utils.generate_qrcode(HandleServer._create_guest_account())
        self.send_response(200)
        self.send_header("Content-type", "text/html")
        self.end_headers()
        html_str = f"""
            <html>
            <head>
                <title>Welcome</title>
                <meta http-equiv='refresh' content='1'>
            </head>
            <body>
                <center>Your account: {HandleServer.account}</center>
                <center>Your password: {HandleServer.account}</center>
                <center><img src='qrcode' alt='Something went wrong' width="40%"></center>
            </body>
            </html>
        """
        self.wfile.write(bytes(html_str, "utf-8"))
        #else:
        #    self._handle_unchanged()

    def _handle_qrcode(self):
        self.send_response(200)
        self.send_header("Content-type", "image/png")
        self.end_headers()
        with open('./qrcode.png', 'rb') as f:
            self.wfile.write(f.read())

    def _handle_favicon(self):
        self.send_response(200)
        self.send_header("Content-type", "/image/jpeg")
        self.end_headers()
        with open('./asset/favicon.png', 'rb') as f:
            self.wfile.write(f.read())

    def _handle_unchanged(self):
        self.send_response(304)
        self.end_headers()

    @classmethod
    def _create_guest_account(cls):
        cls.account = utils.get_random_string(length=const.ACCOUNT_LEN)
        query = f"SELECT count(*) FROM radcheck WHERE username = '{cls.account}';"
        while utils.read_query(cls.cnx, query)[0][0] > 0:
            cls.account = utils.get_random_string(length=const.ACCOUNT_LEN)

        expire_time = datetime.datetime.now() + datetime.timedelta(seconds=const.EXPIRE_TIME)
        if const.EXPIRE_ROUND_TO_DATE:
            expire_str = expire_time.strftime("%d %b %Y 00:00:00")
        else:
            expire_str = expire_time.strftime("%d %b %Y %H:%M:%S")

        queries = [
            f"INSERT INTO radcheck (username, attribute, op, value) VALUES ('{cls.account}', 'Cleartext-Password', ':=', '{cls.account}');",
            #f"INSERT INTO radcheck (username, attribute, op, value) VALUES ('{cls.account}', 'Max-All-Session', ':=', {const.MAX_ALL_SESSION});",
            f"INSERT INTO radcheck (username, attribute, op, value) VALUES ('{cls.account}', 'Expiration', ':=', '{expire_str}');",
            f"INSERT INTO radusergroup (username, groupname) VALUES ('{cls.account}', 'guest');"
        ]
        for query in queries:
            utils.execute_query(cls.cnx, query)
        return cls.account

    @classmethod
    def _guest_account_is_used(cls):
        if cls.account is None:
            return True
        query = f"SELECT count(*) FROM radacct WHERE username = '{cls.account}';"
        return (utils.read_query(cls.cnx, query)[0][0] > 0)

    @classmethod
    def _guest_account_exist(cls):
        query = f"SELECT count(*) FROM radcheck WHERE username = '{cls.account}';"
        return (utils.read_query(cls.cnx, query)[0][0] > 0)

    @classmethod
    def _remove_expired_account(cls):
        query = "SELECT username, value FROM radcheck WHERE attribute = 'Expiration';"
        results = utils.read_query(cls.cnx, query)
        for tmp in results:
            (username, expire_str) = tmp
            expire_time = datetime.datetime.strptime(expire_str, "%d %b %Y %H:%M:%S")

            if expire_time > datetime.datetime.now():
                continue
            queries = [
                f"DELETE FROM radcheck WHERE username = '{username}'",
                f"DELETE FROM radacct WHERE username = '{username}'",
                f"DELETE FROM radusergroup WHERE username = '{username}'"
            ]
            for query in queries:
                utils.execute_query(cls.cnx, query)


class QRCodeServer:
    def __init__(self, hostname='127.0.0.1', port=8888):
        self.hostname = hostname
        self.port = port
        self.webserver = HTTPServer((self.hostname, self.port), HandleServer)
        print(f"[SERVER] - http://{self.hostname}:{self.port}")

    def run(self):
        try:
            self.webserver.serve_forever()
        except KeyboardInterrupt:
            print('[EXIT WITH KEYBOARDINTERRUPT]')
        self.webserver.server_close()


def main():
    server = QRCodeServer(hostname=const.SERVER_HOST, port=const.SERVER_PORT)
    server.run()


if __name__ == "__main__":
    main()
