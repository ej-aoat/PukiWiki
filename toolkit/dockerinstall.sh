#!/bin/bash

# this script target bionic64. 
# see also:https://docs.docker.com/install/linux/docker-ce/ubuntu/

# set up the repository
sudo apt update
sudo apt -y install \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg-agent \
    software-properties-common
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
# point:fingeprint check
# sudo apt-key fingerprint 0EBFCD88

# pub   rsa4096 2017-02-22 [SCEA]
#       9DC8 5822 9FC7 DD38 854A  E2D8 8D81 803C 0EBF CD88
# uid           [ unknown] Docker Release (CE deb) <docker@docker.com>
# sub   rsa4096 2017-02-22 [S]

sudo add-apt-repository \
   "deb [arch=amd64] https://download.docker.com/linux/ubuntu \
   $(lsb_release -cs) \
   stable"
# install docker
sudo apt update
sudo apt -y install docker-ce docker-ce-cli containerd.io

# install docker compose
sudo curl -fsSL "https://github.com/docker/compose/releases/download/1.24.1/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

sudo /bin/systemctl restart docker.service

# Docker系コマンドを一般ユーザーで実行できるようにします。
sudo usermod -aG docker vagrant
