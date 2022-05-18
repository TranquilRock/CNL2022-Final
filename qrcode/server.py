# Python 3 server example
from http.server import BaseHTTPRequestHandler, HTTPServer
import time

from utils import get_random_string, generate_qrcode


class HandleServer(BaseHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super(HandleServer, self).__init__(*args, **kwargs)

    def do_GET(self):
        print(f'[PATH] - {self.path}')
        if self.path == '/':
            self._handle_root()
        elif self.path.startswith('/qrcode'):
            self._handle_qrcode()
        return

    def _handle_root(self):
        self.send_response(200)
        self.send_header("Content-type", "text/html")
        self.end_headers()
        self.wfile.write(bytes("<html><head><link rel='icon' href='data:,'><title>Welcome</title></head>", "utf-8"))
        self.wfile.write(bytes("<center><img src='qrcode' alt='Something went wrong'></center>", "utf-8"))
        self.wfile.write(bytes("<meta http-equiv='refresh' content='1'>", "utf-8"))
        self.wfile.write(bytes("</body></html>", "utf-8"))
        return
    
    def _handle_qrcode(self):
        generate_qrcode(get_random_string(length = 20))
        self.send_response(200)
        self.send_header("Content-type", "image/png")
        self.end_headers()
        with open('./qrcode.png', 'rb') as f:
            self.wfile.write(f.read())
        return


class QRCodeServer:
    def __init__(self, hostname = '140.112.30.57', port = 8081):
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
        return


def main():
    server = QRCodeServer()
    server.run()

if __name__ == "__main__":
    main()