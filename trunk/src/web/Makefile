# this is the openQRM web Makefile
#
# This file is part of openQRM.
#
# openQRM is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License version 2
# as published by the Free Software Foundation.
#
# openQRM is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with openQRM.  If not, see <http://www.gnu.org/licenses/>.
#
# Copyright 2009, Matthias Rechenburg <matt@openqrm.com>
#

export OPENQRM_SERVER_CONF=$(shell pwd)/../etc/openqrm-server.conf

configure:

compile:
	. $(OPENQRM_SERVER_CONF) && ../make-assistant openqrm_get_adodb
	. $(OPENQRM_SERVER_CONF) && ../make-assistant openqrm_get_jquery_bundle
	. $(OPENQRM_SERVER_CONF) && ../make-assistant openqrm_get_js_interface
	. $(OPENQRM_SERVER_CONF) && ../make-assistant openqrm_get_openqrm_clients



install:
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web
	. $(OPENQRM_SERVER_CONF) && cp *.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/
	# action
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/action
	. $(OPENQRM_SERVER_CONF) && cp action/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/action/
	# base
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base
	. $(OPENQRM_SERVER_CONF) && cp base/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/
	# base/include
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/include
	. $(OPENQRM_SERVER_CONF) && cp base/include/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/include/
	. $(OPENQRM_SERVER_CONF) && ../make-assistant openqrm_install_adodb
	# base/js
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/js
	. $(OPENQRM_SERVER_CONF) && cp base/js/*.js $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/js/
	. $(OPENQRM_SERVER_CONF) && ../make-assistant openqrm_install_jquery_bundle
	. $(OPENQRM_SERVER_CONF) && ../make-assistant openqrm_install_js_interface
	# base/class
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/class
	. $(OPENQRM_SERVER_CONF) && cp base/class/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/class/
	# base/tpl
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/tpl
	. $(OPENQRM_SERVER_CONF) && cp base/tpl/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/tpl/
	# base/css
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/css
	. $(OPENQRM_SERVER_CONF) && cp base/css/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/css/
	# base/img/menu
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu
	. $(OPENQRM_SERVER_CONF) && cp base/img/menu/*.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/
	. $(OPENQRM_SERVER_CONF) && cp base/plugins/aa_plugins/img/plugin.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/
	. $(OPENQRM_SERVER_CONF) && cp base/img/image.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/
	. $(OPENQRM_SERVER_CONF) && cp base/img/kernel.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/
	. $(OPENQRM_SERVER_CONF) && cp base/img/resource.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/
	. $(OPENQRM_SERVER_CONF) && cp base/img/storage.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/
	. $(OPENQRM_SERVER_CONF) && cp base/img/active.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/
	. $(OPENQRM_SERVER_CONF) && cp base/img/error.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/
	. $(OPENQRM_SERVER_CONF) && cp base/img/add.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/menu/
	. $(OPENQRM_SERVER_CONF) && cp base/img/*.gif $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/
	. $(OPENQRM_SERVER_CONF) && cp base/img/*.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/img/
	# base/server
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server
	# base/server/aa_server
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/aa_server
	. $(OPENQRM_SERVER_CONF) && cp base/server/aa_server/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/aa_server/
	. $(OPENQRM_SERVER_CONF) && cp base/server/aa_server/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/aa_server/
	. $(OPENQRM_SERVER_CONF) && cp base/server/aa_server/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/aa_server/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/aa_server/img
	. $(OPENQRM_SERVER_CONF) && cp base/server/aa_server/img/*.gif $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/aa_server/img/
	. $(OPENQRM_SERVER_CONF) && cp base/server/aa_server/img/*.jpg $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/aa_server/img/
	. $(OPENQRM_SERVER_CONF) && cp base/server/aa_server/img/*.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/aa_server/img/
	# base/server/resource
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/resource
	. $(OPENQRM_SERVER_CONF) && cp base/server/resource/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/resource/
	. $(OPENQRM_SERVER_CONF) && cp base/server/resource/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/resource/
	. $(OPENQRM_SERVER_CONF) && cp base/server/resource/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/resource/
	# base/server/user
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/user
	. $(OPENQRM_SERVER_CONF) && cp base/server/user/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/user/
	# base/server/image
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/image
	. $(OPENQRM_SERVER_CONF) && cp base/server/image/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/image/
	. $(OPENQRM_SERVER_CONF) && cp base/server/image/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/image/
	. $(OPENQRM_SERVER_CONF) && cp base/server/image/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/image/
	# base/server/appliance
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/appliance
	. $(OPENQRM_SERVER_CONF) && cp base/server/appliance/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/appliance/
	. $(OPENQRM_SERVER_CONF) && cp base/server/appliance/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/appliance/
	. $(OPENQRM_SERVER_CONF) && cp base/server/appliance/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/appliance/
	# base/server/storage
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/storage
	. $(OPENQRM_SERVER_CONF) && cp base/server/storage/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/storage/
	. $(OPENQRM_SERVER_CONF) && cp base/server/storage/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/storage/
	. $(OPENQRM_SERVER_CONF) && cp base/server/storage/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/storage/
	# base/server/kernel
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/kernel
	. $(OPENQRM_SERVER_CONF) && cp base/server/kernel/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/kernel/
	. $(OPENQRM_SERVER_CONF) && cp base/server/kernel/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/kernel/
	. $(OPENQRM_SERVER_CONF) && cp base/server/kernel/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/kernel/
	# base/server/event
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/event
	. $(OPENQRM_SERVER_CONF) && cp base/server/event/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/event/
	. $(OPENQRM_SERVER_CONF) && cp base/server/event/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/event/
	. $(OPENQRM_SERVER_CONF) && cp base/server/event/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/event/
	# base/server/zz_documentation
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/zz_documentation
	. $(OPENQRM_SERVER_CONF) && cp base/server/zz_documentation/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/zz_documentation/
	. $(OPENQRM_SERVER_CONF) && cp base/server/zz_documentation/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/zz_documentation/
	. $(OPENQRM_SERVER_CONF) && cp base/server/zz_documentation/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/server/zz_documentation/
	# base/plugins/aa_plugins
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/plugins/aa_plugins
	. $(OPENQRM_SERVER_CONF) && cp base/plugins/aa_plugins/*.txt $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/plugins/aa_plugins/
	. $(OPENQRM_SERVER_CONF) && cp base/plugins/aa_plugins/*.php $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/plugins/aa_plugins/
	. $(OPENQRM_SERVER_CONF) && cp base/plugins/aa_plugins/*.css $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/plugins/aa_plugins/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/plugins/aa_plugins/img
	. $(OPENQRM_SERVER_CONF) && cp base/plugins/aa_plugins/img/*.png $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/base/plugins/aa_plugins/img/

	echo "Creating the openqrm-client boot-service package"
	. $(OPENQRM_SERVER_CONF) && ../make-assistant openqrm_install_openqrm_clients
	. $(OPENQRM_SERVER_CONF) && mkdir -p $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/boot-service
	. $(OPENQRM_SERVER_CONF) && mkdir -p $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/bin
	. $(OPENQRM_SERVER_CONF) && cp -a ../bin/openqrm-cmd $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/bin/
	. $(OPENQRM_SERVER_CONF) && cp -a $$OPENQRM_BUILD_TMP_DIR/openqrm-build/initroot/bin/dbclient $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/bin/
	. $(OPENQRM_SERVER_CONF) && cp -a $$OPENQRM_BUILD_TMP_DIR/openqrm-build/initroot/bin/dropbearconvert $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/bin/
	. $(OPENQRM_SERVER_CONF) && cp -a $$OPENQRM_BUILD_TMP_DIR/openqrm-build/initroot/bin/dropbearkey $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/bin/
	. $(OPENQRM_SERVER_CONF) && chmod +x $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/bin/*
	. $(OPENQRM_SERVER_CONF) && mkdir -p $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm
	. $(OPENQRM_SERVER_CONF) && mkdir -p $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/sbin
	. $(OPENQRM_SERVER_CONF) && cp -a ../sbin/openqrm* $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/sbin/
	. $(OPENQRM_SERVER_CONF) && cp -a $$OPENQRM_BUILD_TMP_DIR/openqrm-build/initroot/sbin/dropbear $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/sbin/
	. $(OPENQRM_SERVER_CONF) && rm -f $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/sbin/*.c
	. $(OPENQRM_SERVER_CONF) && chmod +x $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/sbin/*
	. $(OPENQRM_SERVER_CONF) && mkdir -p $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/include
	. $(OPENQRM_SERVER_CONF) && cp -a ../include/openqrm-functions $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/include/
	. $(OPENQRM_SERVER_CONF) && cp -a ../include/openqrm-package-functions $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/include/
	. $(OPENQRM_SERVER_CONF) && mkdir -p $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/etc/init.d/
	. $(OPENQRM_SERVER_CONF) && cp -a ../etc/init.d/openqrm-client $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/etc/init.d/
	. $(OPENQRM_SERVER_CONF) && chmod +x $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/$$OPENQRM_RESOURCE_BASE_DIR/openqrm/etc/init.d/*
	. $(OPENQRM_SERVER_CONF) && tar -C 	$$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client/ -czf $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/boot-service/openqrm-client.tgz `echo $$OPENQRM_RESOURCE_BASE_DIR | cut -d'/' -f2-`
	rm -rf $$OPENQRM_BUILD_TMP_DIR/openqrm-build/openqrm-client
	. $(OPENQRM_SERVER_CONF) && ../make-assistant openqrm_install_openqrm_clients

	
uninstall:
	. $(OPENQRM_SERVER_CONF) && rm -rf $(DESTINATION_DIR)$$OPENQRM_WEBSERVER_DOCUMENT_ROOT/openqrm
	. $(OPENQRM_SERVER_CONF) && rm -rf $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web/*
	. $(OPENQRM_SERVER_CONF) && rm -rf $(DESTINATION_DIR)$$OPENQRM_SERVER_BASE_DIR/openqrm/web

clean:

realclean:	clean

all:	configure compile
	
.PHONY: all configure compile install uninstall clean realclean
