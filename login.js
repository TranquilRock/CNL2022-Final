// Get cameras
var cameraId = null;
var NFC_Available = false;
var CAM_Available = false;
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
    if(cameraId != null){
        qrButton.hidden = false;
        CAM_Available = true;
    }
});

try {
    new NDEFReader();
    NFC_Available = true;
    nfcButton.hidden = false;
} catch (error) {
    console.log(error);
}

// document.getElementById('loginframe').hidden = false;

nfcButton = document.getElementById('nfcButton');
qrButton = document.getElementById('qrButton');





Login = document.getElementById('Login');
password = document.getElementById('password');
username = document.getElementById('username');

// Start NFC-SCAN
nfcButton.addEventListener("click", async () => {
    try {
        const ndef = new NDEFReader();
        await ndef.scan();
        ndef.addEventListener("readingerror", () => {
            alert("Argh! Cannot read data from the NFC tag. Try another one?");
        });
        ndef.addEventListener("reading", ({
            message,
            serialNumber
        }) => {
            alert(message)
            alert(`> Serial Number: ${serialNumber} md5 value: ${md5Value}`);
            username.value = serialNumber;
            password.value = serialNumber;
            Login.click();
        });
    } catch (error) {
        alert(error);
    }
});


// Start QR-SCAN

qrButton.addEventListener("click", async () => {
    if (cameraId == null) {
        setTimeout(() => {
            qrButton.click();
        }, 1000);
        return;
    }
    const html5QrCode = new Html5Qrcode("reader", true);
    html5QrCode.start(
        cameraId, {
        fps: 10,
    },
        (decodedText, decodedResult) => {
            html5QrCode.stop().then(() => {
                alert(decodedText);
                username.value = decodedText;
                password.value = decodedText;
                Login.click();
            }).catch((err) => {
                console.log(err);
            });
        },
        (errorMessage) => {
            // console.log(errorMessage);
        })
        .catch((err) => {
            // console.log(err);
        });
});

