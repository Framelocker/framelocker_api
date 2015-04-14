<?php
	$api_host = "http://chat.framelocker.com:8081/";
	//$api_host = "http://localhost:8081/";
?>
<!DOCTYPE html>
<html>
<head>
	<title>Chat</title>
	<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="<?=$api_host?>socket.io/socket.io.js"></script>
</head>
<body>

<div class="container">	
	<div class="panel panel-info" id="panelGeneral">
		<div class="panel-heading">
			<div class="input-group">
	           <input type="text" id="chat_bar" class="form-control" placeholder="Enter Message">
	            <span class="input-group-btn">
	                <button class="btn btn-info" id="send" type="button">SEND</button>
	            </span>
	        </div>
        </div>        
        <div class="panel-body"><button class="btn btn-success" id="inv">Invite</button></div>
        <div class="panel-body">        	
        	<div class="media-list">
        		
        	</div>
        </div>
	</div>	
</div>

<div class="container">
	<div class="row">
		<button class="btn btn-warning" id="checkUsers">Check users</button>
	</div>
    <div class="row">
        <div class="col-md-5">
            <div class="panel panel-primary hide" id="panelPM">
                <div class="panel-heading">
                    <span class="glyphicon glyphicon-comment"></span> Chat
                    <div class="btn-group pull-right">
                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                            <span class="glyphicon glyphicon-chevron-down"></span>
                        </button>
                        
                    </div>
                </div>
                <div class="panel-body">
                    <ul class="chat" style="list-style:none;">
                        <li class="left clearfix chat_el hide"><span class="chat-img pull-left">
                            <img src="#" alt="User Avatar" class="img-circle">
                        </span>
                            <div class="chat-body clearfix">
                                <div class="header">
                                    <strong class="primary-font pm_name"></strong> 
                                    <small class="pull-right text-muted">
                                        <span class="glyphicon glyphicon-time"></span>12 mins ago
                                    </small>
                                </div>
                                <p class="pm_msg">
                                    
                                </p>
                            </div>
                        </li>                      
                        
                    </ul>
                </div>
                <div class="panel-footer">
                    <div class="input-group">
                        <input id="btn-input" type="text" class="form-control input-sm" placeholder="Type your message here...">
                        <span class="input-group-btn">
                            <button class="btn btn-warning btn-sm" id="btn-chat">
                                Send</button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<li class="media hide">
	<div class="media-body">
		<div class="media">
            <a class="pull-left" href="#"><img width="50px" class="media-object img-circle " src=""></a>
            <div class="media-body"><span class="chat_msg">[MSG]</span><br><small class="text-muted"><span class="chat_name">[MSG]</span> | 23rd June at 5:00pm</small><hr></div>
        </div>
    </div>
</li>

<script type="text/javascript">
	(function() {
		var socket = io('<?=$api_host?>api?token=fa1152a47888be2b2500bd9ef543282d0b39e2c8');
		var el = $(".media.hide");
		var pm_el = $(".chat_el");
		if(socket === undefined)
			return false;	
		
		$("#inv").click(function() {
			socket.emit('invite_to_chat', {uid:[8551], room:"hatiko"});
		});	


		socket.on('notifications', function(data){
			console.log(data);
		    if(data.type == 'login' && data.status == 1){
		    	$(document).trigger("success_login");
		    }

		    if(data.request_type == 'invite'){
		        var reponse = confirm('You have recieved an invitation from  '+data.name + ". Accept? Room "+data.room);
		        if(reponse){
		            // Accept invitation
		            socket.emit('accept_invitation', {room: data.room}); // 
		        }
		    }

		    if(data.type == "accepted_invitation"){
		    	$("#panelGeneral").addClass("hide");
		    	$("#panelPM").removeClass("hide");
		    	var udata = data.params.userData;

		    	$("#btn-chat").click(function() {
					var val = $(this).closest(".input-group").find("input").val();
					console.log(data);					
					socket.emit("send_message", {room:data.params.room, msg:val});
				});

				$("#checkUsers").click(function() {
					socket.emit('get_room_users', {room: data.params.room}); 
				});			    	

		    }	
		});

		$(document).on("success_login", function() {
			socket.emit('join_room', {room:"baltazor"});
		});

		$("#send").click(function() {
			var msg = $("#chat_bar").val();
			socket.emit('send_message', {room:"baltazor", msg:msg});
		});

		$("#chat_bar").keypress(function(e){
			if(e.which == 13){
				var msg = $("#chat_bar").val();
				socket.emit('send_message', {room:"baltazor", msg:msg});	
			}
		});

		socket.on('get_messages', function(data){
		    var message_display = $(".media-list");
		    $.each(data, function(i, val){
		        var chat = el.clone();
		        chat.removeClass("hide");
		        chat.find(".chat_msg").text(val.msg);
		        chat.find("img").attr("src", val.avatar);
		        chat.find(".chat_name").text(val.name);
		        message_display.append(chat);            
		    });
		});

	})()
	
</script>
</body>
</html>