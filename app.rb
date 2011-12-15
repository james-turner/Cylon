require 'prettyprint'
require 'json'


epg = JSON.parse open("http://epgservices.sky.com/5.1.1/api/2.1/channel/json/2002/1293289200/7200/1").readline.to_s

pp epg