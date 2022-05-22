# Python 3 server example
from http.server import BaseHTTPRequestHandler, HTTPServer
import time
import utils
import const


class HandleServer(BaseHTTPRequestHandler):
    cnx = utils.create_mysql_connection("127.0.0.1",
            const.ROOT_ACCOUNT, const.ROOT_PASSWORD, const.DATABASE)
    # new guest account and password
    account = None
    password = None

    def __init__(self, *args, **kwargs):
        super(HandleServer, self).__init__(*args, **kwargs)

    def do_GET(self):
        print(f'[PATH] - {self.path}')
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
        cls.password = utils.get_random_string(length=const.PASSWORD_LEN)
        # TODO: save into database
        return cls.account + cls.password

    @classmethod
    def _guest_account_is_used(cls):
        if cls.account is None:
            return True
        # TODO
        return False


class QRCodeServer:
    def __init__(self, hostname='127.0.0.1', port=8081):
        self.hostname = hostname
        self.port = port
        self.webserver = HTTPServer((self.hostname, self.port), HandleServer)
        print(f'[SERVER] - http://{hostname}:{port}')

    def run(self):
        try:
            self.webserver.serve_forever()
        except KeyboardInterrupt:
            print('[EXIT WITH KEYBOARDINTERRUPT]')
        self.webserver.server_close()


def main():
    server = QRCodeServer(hostname='127.0.0.1', port=8081)
    server.run()


if __name__ == "__main__":
    main()
