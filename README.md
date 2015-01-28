# RouterOS DNS Server AdBlock Configuration

This repository contains a PHP script that will convert some of the filter lists used by AdBlock and uBlock into RouterOS commands to add static entries to the router's DNS server (`/ip dns static`). Combined with an additional firewall (`/ip firewall filter`) rule, shown below, this has the effect of blocking adverts (and/or malware, scams, etc., depending on the chosen lists) for any clients which are configured to use the router as their DNS server.

By blocking ads at the DNS server level, adverts are blocked even for locked-down devices such as smartphones and games consoles!

Note that it's generally easiest to configure your DHCP server to push DNS server entries to your clients, where those server entries point back to your router. That way, everything on your network will automatically use the ad-blocking DNS server.

## Usage ##
1. Add a firewall filter rule to block outbound access to the **240.0.0.0/4** IPv4 range, rejecting those attempts with a **TCP Reset**. If a reject option besides `tcp-reset` is chosen, browsers attempting to load an advert will wait several seconds before timing out; `tcp-reset` forces them to fail instantly instead. *Remember to change the `in-interface` below to match your configuration!*
    `/ip firewall filter add chain=forward in-interface=LAN connection-state=new protocol=tcp dst-address=240.0.0.0/4 action=reject reject-with=tcp-reset`
2. Ensure your router's DNS server is configured as desired.
3. Edit `process.php`, commenting-out the filter list files that you don't want to use. **NOTE**: my experience is the MikroTik RouterBoard devices (especially the consumer-level ones) aren't sufficiently powerful to use more than a few of these lists simultaneously. My own RouterBoard (RB450G), when presented with 60,000 static DNS entries, kept working quite happily for several hours until it tried reloading the static DNS entry list, which took it 16 minutes, during which time the DNS server wouldn't respond and even basic traffic forwarding performance was impacted. I **strongly suggest** that you only use one or two of these lists!
4. If you have any specific hosts that you want to block, add them to `source.custom.txt`.
5. Note that if you decide to enable the "remove duplicates" option in `process.php`, you will find that the script runs *significantly* faster under HHVM rather than standard PHP. In my tests, HHVM processed all of the input files in 20 seconds while standard PHP took 275 seconds!
6. Execute `process.php`:
    `php process.php` or `hhvm process.php`
7. The script will create a number of script.*.rsc files. Since my RouterBoard's input buffer was quite limited, I had the script split the output files into separate files. You can now copy these files, one at a time (allowing each file to finish being processed before loading the next one!), into a RouterOS terminal.
8. Configure your router's DHCP server to push DNS settings that include using the router as clients' main DNS resolver.

Note: the 240.0.0.0/4 range is listed as as reserved range, which is why that range was chosen to redirect advert requests to. I chose to have the DNS server respond with a 240.0.0.x address rather than something like 0.0.0.1 (RouterOS itself refuses to let you have a static DNS entry pointing to 0.0.0.0) because some devices/software seem to actually try making a connection to 0.0.0.x addresses and will wait until they time out before the web page finishes loading. Similarly, the loopback range (127.0.0.0/8) wasn't chosen because some devices will be listening on port 80 for one reason or another (weirdly, Skype on Windows does this, seemingly as a way around firewalls, but mostly as a way to annoy anyone trying to set up Apache), and making a request that a local web server has to handle, thus increasing the load on the device, would be going against one of the primary reasons for blocking ads in the first place!
