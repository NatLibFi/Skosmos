# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|

  config.vm.box = "ubuntu/xenial64"
  config.vm.network "forwarded_port", guest: 80, host: 8040
  config.vm.post_up_message = "Skosmos up and running at localhost:8040/Skosmos"

  config.vm.synced_folder "", "/var/www/html/Skosmos"

  config.vm.provider "virtualbox" do |vb|
    vb.memory = "4096"
	vb.cpus = "2"
	# disable creating a log file to root folder:
	vb.customize [ "modifyvm", :id, "--uartmode1", "disconnected" ]
  end

  config.vm.provision "ansible" do |ansible|
    ansible.playbook = "ansible/playbook.yml"
	ansible.verbose = "vvv"
	ansible.compatibility_mode = "2.0"
  end

end
