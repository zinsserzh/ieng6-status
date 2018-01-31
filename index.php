<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

require_once('prepare.php');
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>ieng6-ece server status</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://code.jquery.com/jquery-3.2.1.min.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/vue"></script>
        <script src="https://js.pusher.com/4.1/pusher.min.js"></script>
        <style>
            .progress-bar {
                max-width: 100%;
            }
            .progress {
                border: 1px solid gray;
                margin: 0px !important;
                background-color: #555 !important;
            }

            .highlight {
                background-color: #6c757d !important;
                transition: 0s !important;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="jumbotron">
                <h1>ieng6-ece-*.ucsd.edu server status</h1>
            </div>
            <div class="alert alert-danger">
                This page is only for UCSD ECE students to pick a server and balance servers' load. Data provided may not be accurate or even valid. Use this page for good and at your own risk! Good luck on your project!
            </div>
            <table class="table table-dark table-striped" id="app">
                <thread>
                    <tr>
                        <th>Server</th>
                        <th>Status</th>
                        <th>Last Contact</th>
                        <th>#Users</th>
                        <th>1 min cpu load avg.</th>
                        <th>5 min cpu load avg.</th>
                        <th>15 min cpu load avg.</th>
                    </tr>
                </thread>
                <tbody>
                    <tr is="server"
                        v-for="status in data_array"
                        v-bind:status="status"
                        v-bind:key="status.hostname"
                        v-bind:now="now"
                    ></tr>
                </tbody>
            </table>
            <div class="alert alert-info">
                <h4>What does CPU load average mean? Is it just CPU usage? Why is maximum 8.00?</h4>
                <ul>
                    <li>CPU usage (the number you always pay most attention to in "Windows Task Manager") is roughly CPU load average/number of cores.</li>
                    <li>Think like this: CPU load average is how much you and other users (and other system processes) ask from CPU. CPU usage is how much it actually gives.</li>
                    <li>Each of these servers has 8 cores, so I set maximum to 8.00. When CPU load average hits 8.00, CPU usage will be roughly 100%.</li>
                    <li>CPU load average is possible to go above 8.00. When it does, CPU usage is probably/hopefully 100%</li>
                    <li>For detailed explanation of CPU load average, <a href="http://blog.scoutapp.com/articles/2009/07/31/understanding-load-averages" target="_blank"><strong>this page</strong></a> is enough for you to understand it.</li>
                </ul>
            </div>
            <div class="alert alert-warning">
                <strong>Notice that CPU load is not the only thing out there to make sure you have a smooth experience.</strong> However, it should give you some sense how busy these servers are. Other factors include memory usage, NFS usage/latency, network usage/latency, etc.
            </div>
            <div class="well well-sm" style="margin-bottom:10px">
                Page created by <a href="/" target="_blank">Zinsser</a>  <a href="http://s09.flagcounter.com/more/juoz/" target="_blank"><img src="http://s09.flagcounter.com/mini/juoz/bg_FFFFFF/txt_000000/border_CCCCCC/flags_0/"/></a> <a href="https://github.com/zinsserzh/ieng6-status" target="_blank"> View this project on <img src="https://assets-cdn.github.com/images/modules/logos_page/GitHub-Logo.png" style="height:15px; margin-left: 5px"/></a>
            </div>


        </div>

        <script>
            Vue.component('load', {
                props: ['load'],
                template:
                    '<td><div class="progress" style="height:24px"><div class="progress-bar progress-bar-striped" role="progressbar" v-bind:class="class_object" v-bind:style="style_object">{{ formatted_load }}</div></div></td>',
                computed: {
                    class_object: function() {
                        return {
                            "bg-success": this.load < 1.0,
                            "bg-warning": this.load >= 1.0 && this.load < 4.0,
                            "bg-danger": this.load >= 4.0,
                            "progress-bar-animated": true,
                        }
                    },

                    style_object: function() {
                        return {"width": this.load * 100.0 / 8.0 + "%"};
                    },

                    formatted_load: function() {
                        return Number(this.load).toFixed(2);
                    },
                }
            });

            Vue.component('server', {
                props: ['status', 'now'],
                template:
                    '<tr v-bind:class="class_object" style="transition:all 1s ease-in-out">' +
                        '<td>{{ status.hostname }}</td>' +
                        '<td>{{ formatted_status }}</td>' +
                        '<td>{{ formatted_last_contact }}</td>' +
                        '<td>{{ status.users }}</td>' +
                        '<load v-bind:load="status.load_1min"></load>' +
                        '<load v-bind:load="status.load_5min"></load>' +
                        '<load v-bind:load="status.load_15min"></load>' +
                    '</tr>',
                computed: {
                    gone: function() {
                        return this.formatted_last_contact == '10+ min ago';
                    },
                    busy: function() {
                        return ((this.status.load_1min >= 1.0) +
                            (this.status.load_5min >= 1.0) +
                            (this.status.load_15min >= 1.0)) >= 2 && !this.full;
                    },
                    full: function() {
                        return ((this.status.load_1min >= 4.0) +
                                (this.status.load_5min >= 4.0) +
                                (this.status.load_15min >= 4.0)) >= 2;
                    },
                    class_object: function() {
                        return {
                            "highlight": this.status.highlight,
                            "table-dark": this.gone,
                            "bg-danger": this.full,
                            "bg-warning": this.busy,
                        }
                    },
                    formatted_status: function() {
                        if (this.gone)
                            return 'Gone';
                        else if (this.full)
                            return 'Full';
                        else if (this.busy)
                            return 'Busy';
                        else
                            return 'Idle';
                    },
                    formatted_last_contact: function() {
                        td = this.now - this.status.last_contact;
                        if (td < 60)
                            return 'Just Now';
                        else if (td < 600)
                            return Math.floor(td/60.0) + ' min ago';
                        else
                            return '10+ min ago';
                    },
                },
            });

            var data = <?php echo json_encode(prepare_data());?>;

            function process_data (data) {
                var data_array = new Array();

                for (hostname in data) {
                    server = data[hostname];
                    server.highlight = 0;
                    data_array.push(server);
                }

                data_array.sort(function(a, b) {
                    if (a.hostname < b.hostname)
                        return -1;
                    if (a.hostname > b.hostname)
                        return 1;

                    return 0;
                });

                return data_array;
            }

            var app = new Vue({
                el: "#app",
                data: {
                    data_array: process_data(data),
                    now: Math.floor(+Date.now() / 1000.0),
                },
                methods: {
                    update_now: function() {
                        this.now = Math.floor(+Date.now() / 1000.0);
                    },
                },
            });

            setInterval(app.update_now, 5000);

            function flash(server) {
                server.highlight = 1;
                setTimeout(function() {
                    server.highlight = 0;
                }, 500);
            }

            function reload() {
                $.get('api.php', function(new_data) {
                    data = new_data;
                    app.data_array = process_data(data);
                });
            }
        </script>
        <script>
            // Enable pusher logging - don't include this in production
            // Pusher.logToConsole = true;

            var pusher = new Pusher("<?=getenv("PUSHER_KEY");?>", {
            cluster: 'us2',
                encrypted: true
            });

            var channel = pusher.subscribe('status');
            channel.bind('pusher:subscription_succeeded', reload);
            channel.bind('update', function(server_data) {
                server = data[server_data.hostname];
                server.users = server_data.users;
                server.load_1min = server_data.load_1min;
                server.load_5min = server_data.load_5min;
                server.load_15min = server_data.load_15min;
                server.last_contact = Math.floor(+Date.now() / 1000.0);
                flash(server);
            });
        </script>
    </body>
</html>
