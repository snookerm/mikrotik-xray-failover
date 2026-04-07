# Building a Docker Image for MikroTik Manually
Версия документа на русском языке: [docker/readme.md](readme.md)

## Description
This guide describes how to:
- Use a pre-built Docker image
- Build a `.tar` image manually
- Properly prepare an image for MikroTik (RouterOS)

---

## Quick Container Launch in MikroTik
Example for the xray container:
```routeros
/container/add file=usb1-part1/xray-images/xray-mikrotik-26.2.9-arm64.tar \
    interface=docker-xray-veth \
    root-dir=usb1-part1/xray-root \
    mountlists=XRAYCFG \
    name=xray \
    logging=yes \
    start-on-boot=yes
```

---

## Pre-built Image

You can download an already built image [here](https://any.hayazg.net/xray-mikrotik-26.2.9-arm64.tar).
There are situations where MikroTik doesn't have access to github.com, or simply for reliability, you want to keep the image file on a USB drive in MikroTik. In that case, you need to generate the tar archive of the image yourself. Generating the image is also required when you want to customize the Image (change something in the Dockerfile or start.sh) and rebuild the image.

---

## Important (Windows + Docker Desktop)

Images built via Docker Desktop are often **NOT compatible** with MikroTik.

Reasons:
- Incompatible `docker save` format
- Additional metadata (provenance / SBOM)
- Specifics of `buildx`

Discussion:
https://forum.mikrotik.com/t/container-cant-running-on-arm64-routeros/164606

MikroTik recommends using **Podman**

---

# Building on Windows (via Podman + WSL2)

## 1. Installing Podman
Download:
https://github.com/containers/podman/releases

Install to:
```
C:\Program Files\RedHat\Podman\
```

---

## 2. Adding to PATH

### Temporarily (PowerShell)
```powershell
$env:PATH += ";C:\Program Files\RedHat\Podman\"
```

### Permanently
1. Win + R -> `sysdm.cpl`
2. Tab **Advanced -> Environment Variables**
3. Add to `Path`:
```
C:\Program Files\RedHat\Podman\
```

Restart PowerShell

Verify (PowerShell):
```powershell
$env:PATH -split ";"
```

---

## 3. Initializing Podman (PowerShell)

```powershell
podman machine init
podman machine start
podman --version
```

---

## 4. Building the Image (PowerShell)

```powershell
cd e:\xray-mikrotik

podman build --platform linux/arm64 --tag xray-mikrotik:26.2.9 .

podman save --format docker-archive -o xray-mikrotik-26.2.9-arm64.tar xray-mikrotik:26.2.9
```

Output file:
```
E:\xray-mikrotik\xray-mikrotik-26.2.9-arm64.tar
```

Transfer to MikroTik:
```
usb1-part1/xray-images/
```

---

# Building on Linux (Ubuntu)

## 1. Installation and Preparation

```bash
sudo mkdir -p /opt/mikrotik
cd /opt/mikrotik

sudo apt install podman

podman pull --arch=arm64 docker.io/snookerm/xray-mikrotik:26.2.9

podman save snookerm/xray-mikrotik:26.2.9 > xray-mikrotik-26.2.9-arm64.tar
```

---

## 2. Transferring the File

File:
```
/opt/mikrotik/xray-mikrotik-26.2.9-arm64.tar
```

Transfer to MikroTik:
```
usb1-part1/xray-images/
```

---

## 3. Rebuilding the Container in MikroTik
Example for the xray container:
```routeros
/container/stop xray
/container/remove xray

/container/add file=usb1-part1/xray-images/xray-mikrotik-26.2.9-arm64.tar \
    interface=docker-xray-veth \
    root-dir=usb1-part1/xray-root \
    mountlists=XRAYCFG \
    name=xray \
    logging=yes \
    start-on-boot=yes
```

---

# Summary

- Use **Podman**, not Docker Desktop
- Build the image for `linux/arm64`
- Store `.tar` on USB (more reliable)
- Use a pre-built image or customize via Dockerfile

---

# Tips

- You can customize `Dockerfile` and `start.sh`
- You can store multiple images on the USB drive
- Suitable for offline installations

---
