# FT-TXT
This repository contains the firmware for the TXT controller based on BUILDROOT (see [BuildrootManual](https://buildroot.org/downloads/manual/manual.pdf)).

If you have any questions regarding the software, please contact `fischertechnik-technik@fischer.de`.

## Quick Start
If the development environment already exists, you can use these commands to create a firmware image for the TXT controller.
  ```
  cd ./FT-TXT
  git pull
  ./Make-TXT-Bootloader.sh
  ./Make-TXT-Buildroot-Clean.sh
  sudo ./Make-TXT-Image.sh
  ```
The compressed generated image you can find in `../ft-TXT_Build_XXX.img.zip`

## Setup Build Environment
To build the *BSB* and the *Bootloader* you will need a linux system with a development environment.
The making of *BSP* was tested with [Ubuntu Mate 16.04](http://cdimage.ubuntu.com/ubuntu-mate/releases/16.04.4/release/ubuntu-mate-16.04.4-desktop-amd64.iso):
Install the following linux packages first:
  ```
  cd ./FT-TXT
  sudo ./Linux-Pakete-Required.sh
  (sudo ./Linux-Pakete-Extra.sh)
  ```

## Making of Rootfs, Bootloader and Kernel
### Once
1. Create working directory and change to the directory
  ```
  mkdir FT
  cd FT
  ```

2. Clone the FT-TXT repository
  ```
  git clone https://github.com/fischertechnik/FT-TXT.git
  (git clone https://gitlab.com/fischertechnik/FT-TXT.git)
  ```

3. Create directory for the toolchain
  ```
  sudo mkdir /opt/FT
  sudo chmod a+rw /opt/FT
  ```
### Frequently
4. Change to the directory
  ```
  cd FT-TXT
  ```	

5. Build Bootloader
  ```
  ./Make-TXT-Bootloader.sh
  ```
  erstellt die Bootloader Binaries in `../u-boot/bin`

6. Clone, configure and build Buildroot
  ```
  ./Make-TXT-Buildroot-Clean.sh
  ```
  Damit wird *buildroot* gecloned und der richtige commit eingestellt, Patches eingespielt und Hilfsscripte kopiert. Anschließend wird *buildroot* gebaut.
  
  Die Ausgaben sind in `FT-TXT/../buildroot/output/images` zu finden.

  Eine inkrementelle rekonfiguration mit inkrementellen bauen kann über das Script
  ```
  ./Make-TXT-Buildroot-Incremental.sh
  ```

7. Create Update scripts
  ```
  ./Make-TXT-UpdateScripts.sh
  ./Sign-Connect-Reader.sh
  ./Sign-TXT-UpdateScripts.sh
  ```
  Update scripts can be used to update the OS on a TXT without using a flash card.
  The update scripts and signatures can be found in `FT-TXT/../update`.

8. Create Graphs
```
./Make-TXT-Graphs.sh
```

Graphing the dependencies between packages, build duration and filesystem size contribution of packages. Required packages: `sudo apt install python-matplotlib python-numpy`
You will find the generated graphs in `FT-TXT/../buildroot/output/graphs/`.

## Scripts

### Make-TXT-Image.sh

Erstellt ein SD Kartenimage mit Bootsektion und Rootfilesystem

Imageerstellung [kpartx](https://robert.penz.name/73/kpartx-a-tool-for-mounting-partitions-within-an-image-file/)

im Verzeichnis ./FT-TXT
```
sudo ./Make-TXT-Image.sh
```
es entstehen dann Dateien im übergeordneten Verzeichnis
```
ls -al ../ft*

-rw-r--r-- 1 root root 372244480 Apr  9 11:29 ../ft-TXT_Build_266.img
-rw-r--r-- 1 root root 106355379 Apr  9 11:29 ../ft-TXT_Build_266.img.zip

```
Dieses Image kann dan mit dem dd Befehl auf eine SD Karte kopiert werden.
```
sudo dd if=../ft-TXT_Build_266.img of=/dev/mmcblk0 bs=16M;sync
```
Wenn das host Linux nur standard disk devices kennt, kann man alternativ den Befehl
```
sudo ./Copy-TXT-Image-to-SD.sh /dev/sdX
```
verwenden. Dieses Script prüft ob das Zielgerät eine für SD Karten übliche Größe hat (die exakte größe muss ggf. in das Script eingetragen werden). Dadurch wird vermieden, dass man durch einen Tippfehler die Systemplatte löscht.

### Map-TXT-Image.sh

mittels MapImage.sh kann das erstellte Image "gemapped" werden.

```
sudo ./Map-TXT-Image.sh

>>> ../ft-TXT_Build_266.img <<<
>>>>>>>>>>>>>>>>>>
/dev/mapper/loop0p1
/dev/mapper/loop0p2
>>>>>>>>>>>>>>>>>>
./MapImage.sh: 17: ./MapImage.sh: usleep: not found
add map loop0p1 (253:0): 0 266240 linear 7:0 2048
add map loop0p2 (253:1): 0 458752 linear 7:0 268288
MAP IMAGE:root@NB-RU:/hdd2/ru/FT/BUILD/FT-TXT# 


```
Sie befinden sich dann in einer Shell 
das image bzw die Dateien) finden Sie unter

```
/tmp/boot
/tmp/rootfs
```
```
df -h

Filesystem           Size  Used Avail Use% Mounted on
udev                 7.8G     0  7.8G   0% /dev
tmpfs                1.6G   18M  1.6G   2% /run
/dev/sda1            466G  406G   37G  92% /
tmpfs                7.8G   17M  7.8G   1% /dev/shm
tmpfs                5.0M  4.0K  5.0M   1% /run/lock
tmpfs                7.8G     0  7.8G   0% /sys/fs/cgroup
/dev/sdb1            459G  401G   35G  93% /hdd2
tmpfs                1.6G   32K  1.6G   1% /run/user/1000
/dev/mapper/loop0p1  128M   70M   59M  55% /tmp/boot
/dev/mapper/loop0p2  213M  102M  100M  51% /tmp/rootfs

```
zum Verlassen:
```
exit
```

### Split-TXT-UpdateScripts.sh

Dieses Script extrahiert das in `FT-TXT/../update/update-2.sh` enthaltene tar.gz file des rootfs.
Das ist hauptsächlich sinnvoll, wenn man sich nicht so ganz sicher ist, was in einem `update-2.sh` script enthalten ist.
Das Script hat keine Parameter. Das Ergebnis wird nach `FT-TXT/../update/update-2.tar.gz` geschrieben.

### Test Build Packages in BUILDROOT
```
cd ../buildroot
make menuconfig
make savedefconfig
```
You will find the generated config in `FT-TXT/configs/FT-TXT_4.1_defconfig`.
