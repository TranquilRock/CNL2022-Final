import random
import string
import qrcode

def get_random_string(length = 10):
    return ''.join([random.choice(string.ascii_letters) for _ in range(length)])

def generate_qrcode(text, path = './qrcode.png'):
    img = qrcode.make(text)
    img.save(path)
    print(f'[STORE QRCODE] - {path}')
    return img