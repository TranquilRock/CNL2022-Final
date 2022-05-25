// Get cameras
var cameraId = null;
var NFC_Available = false;
var CAM_Available = false;
nfcButton = document.getElementById('nfcButton');
qrButton = document.getElementById('qrButton');
typeinButton = document.getElementById('typeinButton');
loading = document.getElementById('loading');
loading.src = "./lib/images/loading.gif";
scanning = document.getElementById('scanning');

try {
    new NDEFReader();
    NFC_Available = true;
} catch (error) {
    nfcButton.hidden = true;
}

Html5Qrcode.getCameras().then(devices => {
    console.log(devices); // Show all device in camera.
    var flag = true;
    for (i in devices) {
        device = devices[i]
        if (device['label'].toLowerCase().includes('rear') || device['label'].toLowerCase().includes('back')) {
            cameraId = device.id;
            flag = false;
        }
    }
    if (flag) {
        cameraId = devices[0].id;
    }
    if (cameraId != null) {
        CAM_Available = true;
    }
}).catch(e => { }).then(() => {
    loading.hidden = true;

    if (CAM_Available) {
        qrButton.hidden = false;
    }
    if (NFC_Available) {
        nfcButton.hidden = false;
    }
    if (!(CAM_Available || NFC_Available)) {
        document.getElementById('loginframe').hidden = false;
    }else{
        typeinButton.hidden = false;
    }
});


Login = document.getElementById('Login');
password = document.getElementById('password');
username = document.getElementById('username');

// Start NFC-SCAN

nfcButton.addEventListener("click", async () => {
    nfcButton.hidden = true;
    qrButton.hidden = true;
    // typeinButton.hidden = true;
    scanning.hidden = false;
    try {
        var ndef = new NDEFReader();
        await ndef.scan();
        ndef.addEventListener("reading", (message, serialNumber) => {
            username.value = serialNumber;
            password.value = serialNumber;
            Login.click();
        });
    } catch (error) {
        alert(error);
    }
});

scanning.addEventListener("click", async () => {
    nfcButton.hidden = false;
    scanning.hidden = true;
    qrButton.hidden = !CAM_Available;
});

typeinButton.addEventListener('click', () => {
    typeinButton.hidden = true;
    document.getElementById('loginframe').hidden = false;
});


// Start QR-SCAN
const html5QrCode = new Html5Qrcode("reader", true);

qrButton.addEventListener("click", async () => {
    try {
        html5QrCode.start(cameraId, { fps: 10, },
            (decodedText, decodedResult) => {
                html5QrCode.stop().then(() => {
                    alert(decodedText);
                    username.value = decodedText;
                    password.value = decodedText;
                    Login.click();
                }).catch((err) => {});
            },
            () => { }
        ).catch(() => { });
    } catch (e) {
        html5QrCode.stop();
    }
});

