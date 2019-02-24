Vagrant.configure("2") do |config|
  config.vm.box = "centos/7"
  config.vm.network "private_network", ip: "192.168.33.10"
  #config.vm.provision :shell, path: "bootstrap.sh"
  #config.vm.synced_folder ".", "/vagrant", type:"virtualbox"
  config.vm.synced_folder ".", "/vagrant", type:"virtualbox" , mount_options: ["uid=33", "gid=33"]
  config.vm.provider "virtualbox" do |v|
    v.customize ["modifyvm", :id, "--ostype", "RedHat_64"]
  end
  
  config.vm.provision :docker, run: "always"
  config.vm.provision :docker_compose,
    yml: "/vagrant/docker-compose.yml",
    compose_version: "1.21.2",
    run: "always"
end
