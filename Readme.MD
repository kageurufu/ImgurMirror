A basic caching imgur mirror script in PHP

I would recommend using a script like this in your crontab to clear up old files

*/60 * * * * find ~/kageurufu.net/imgur/cache -type f -mtime +7 -delete

this will keep your cache from growing too big, if you are limited on space

put this on your web server, create a folder named "cache" (or edit the script to point elsewhere)

Set cache to be non-execute, in whatever webserver you use to prevent exploits being uploaded.

You can also put the cache outside your web directory to keep it more secure.