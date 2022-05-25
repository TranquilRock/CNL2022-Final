from http.server import BaseHTTPRequestHandler, HTTPServer
import time
import utils
import const


class HandleServer(BaseHTTPRequestHandler):
    cnx = utils.create_mysql_connection(const.SQL_HOST,
            const.ROOT_ACCOUNT, const.ROOT_PASSWORD, const.DATABASE)
    # new guest account (and same password)
    account = None

    def __init__(self, *args, **kwargs):
        super(HandleServer, self).__init__(*args, **kwargs)

    def do_GET(self):
        print('[PATH] - {p}'.format(p=self.path))
        if self.path == '/':
            self._handle_root()
        elif self.path.startswith('/qrcode'):
            self._handle_qrcode()

    def _handle_root(self):
        self.send_response(200)
        self.send_header("Content-type", "text/html")
        self.end_headers()
        self.wfile.write(bytes("<html><head><link rel='icon' href='data:,'><title>Welcome</title></head>", "utf-8"))
        self.wfile.write(bytes("<center><img src='qrcode' alt='Something went wrong'></center>", "utf-8"))
        self.wfile.write(bytes("<meta http-equiv='refresh' content='1'>", "utf-8"))
        self.wfile.write(bytes("</body></html>", "utf-8"))

    def _handle_qrcode(self):
        if HandleServer._guest_account_is_used():
            utils.generate_qrcode(HandleServer._create_guest_account())
        self.send_response(200)
        self.send_header("Content-type", "image/png")
        self.end_headers()
        with open('./qrcode.png', 'rb') as f:
            self.wfile.write(f.read())

    @classmethod
    def _create_guest_account(cls):
        cls.account = utils.get_random_string(length=const.ACCOUNT_LEN)
        query = "SELECT count(*) FROM radcheck WHERE username = {account};".format(account=cls.account)
        while utils.read_query(cls.cnx, query)[0][0] > 0:
            cls.account = utils.get_random_string(length=const.ACCOUNT_LEN)
        query = """
            INSERT INTO radcheck (username, attribute, op, value)
            VALUES ('{account}', 'Cleartext-Password', ':=', '{account}');
            INSERT INTO radcheck (username, attribute, op, value)
            VALUES ('{account}', 'Max-All-Session', ':=', {session});
            INSERT INTO radusergroup (username, groupname)
            VALUES ('{account}', 'guest');
        """.format(account=cls.account, session=const.MAX_ALL_SESSION)
        #INSERT INTO radcheck (username, attribute, op, value)
        #VALUES ('{cls.account}', 'Max-All-Traffic', ':=', {const.MAX_ALL_TRAFFIC});
        execute_query(cls.cnx, query)
        return cls.account

    @classmethod
    def _guest_account_is_used(cls):
        if cls.account is None:
            return True
        query = "SELECT count(*) FROM radacct WHERE username = {account}".format(account=cls.account)
        return (utils.read_query(cls.cnx, query)[0][0] > 0)

    @classmethod
    def _remove_all_guest_account(cls):
        pass


class QRCodeServer:
    def __init__(self, hostname='127.0.0.1', port=8888):
        self.hostname = hostname
        self.port = port
        self.webserver = HTTPServer((self.hostname, self.port), HandleServer)
        print("[SERVER] - http://{h}:{p}".format(h=hostname, p=port))

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
