// Get cameras
var cameraId = null;
var NFC_Available = false;
var CAM_Available = false;
nfcButton = document.getElementById('nfcButton');
qrButton = document.getElementById('qrButton');
loading = document.getElementById('loading');
loading.src = "./lib/images/loading.gif";

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
    if (CAM_Available) {
        loading.hidden = true;
        qrButton.hidden = false;
    }
    if (NFC_Available) {
        loading.hidden = true;
        nfcButton.hidden = false;
    }
    if (!(CAM_Available || NFC_Available)) {
        loading.hidden = true;
        document.getElementById('loginframe').hidden = false;
    }
});

Login = document.getElementById('Login');
password = document.getElementById('password');
username = document.getElementById('username');

// Start NFC-SCAN
const abortController = new AbortController();
abortController.signal.onabort = event => {
    alert("Success");
};
document.querySelector("#abortnfcButton").onclick = event => {
    abortController.abort();
};

nfcButton.addEventListener("click", async () => {
    try {
        var ndef = new NDEFReader();
        await ndef.scan({ signal: abortController.signal });
        ndef.addEventListener("reading", (message, serialNumber) => {
            username.value = serialNumber;
            password.value = serialNumber;
            Login.click();
        });
    } catch (error) {
        alert(error);
    }
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

