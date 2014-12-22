Framelocker API Docs
========
<h3>Navigation</h3>
1. [Server API](#server-api)
2. [NodeJS Chat API](#chat-api-docsnodejs-socket)
3. [NodeJS Chat API Invitation](#invitation-for-chat)

#Server API

<h4>API admin page - http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/novp/</h4>
<h4>API access - http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/api</h4>
<h4>Table 1.1 - API methods</h4>

 # | Method        | TYPE | Request                                                        | Response                              |
---|---------------| -----|----------------------------------------------------------------|---------------------------------------|
 1 | upload_file   | POST | {method:upload_file, token, novp_file}                         | {status, description, [title, src]}   |
 2 | get_files     | GET  | {method:get_files, token}                                      | {status, description,                 |
   |			   |	  |															 	   | [file_data:{id,user_id,bucket_id,     |
   |               |      |                                                        		   | filename,size,ext,aws,date}]}         |
 3 | register      | POST | {method:"register", params: {username, password, [novp_file]}} | {status, description}                 |     
 4 | signin        | POST | {method:"signin", params:{login, pass}}                        | {status, description, <b>token</b>}   |
 5 | signout       | POST | {method:"signout", token}                                      | {status, description}                 |         
 6 | upload_avatar | POST | {method:"upload_avatar", token, novp_file}                     | {status, description, [filename]}     |          
 7 | set_name      | POST | {method:"set_name", token, params: {fstname, lstname}}         | {status, description}                 |   
---|---------------|------|----------------------------------------------------------------|---------------------------------------|
   |               |      |    <h3>For WOD chat</h3>                                       |                                       | 
 8 | get_boxes     | GET  | {method:"get_boxes", token}                                    | {status, description, boxes}          |
 9 | assign_box    | POST | {method:"assign_box", token, params: {uid, box}}               | {status, description}                 |
 
<h4>Example of usage</h4>
<p>We need to know about user's files</p>
<p>Steps:</p>
<p><i>1. Authorization </i></p>
<p><i>2. Get user's files </i></p>

<p>1. Sending POST request to http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/api with data:{method:"signin", params:{"Alexey", "1234"}}</p>
<p>2. Catching response from API and obtaining <b>token</b> <i></p>
<p>3. Using token for method [get_files] - http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/api?method=get_files&token=91c26f0fec6f834d928fcc644ef8532849803f77. We'll receive json with status(1-ok,0-error,...), description(Text for human) and json array with file's info</p>	


<h4>Small sample of code</h4>

	```
	
	$(function(){
		$.ajax({
			type: "POST",
			url: "http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/api",
			dataType: "json",
			data: { method:"signin", params:{login:"alexey@oxford.com", pass:"mypass123"}},
			success: function(data){
				// Using data
			}
		});
	});
	
	```

	
#Chat API Docs(NodeJS Socket)

<h3> Socket connection </h3>

1. Enable socket script to site where API will be used:

	
	```javascript

	<script type="text/javascript" src="http://[domain]/socket.io/socket.io.js"></script> 

	```

	> [Domain] - ec2-54-68-182-31.us-west-2.compute.amazonaws.com


2. Obtain token with authorization previous method (signin):


	```javascript

	var token = data.token;
		 
	```

	> where [data] json response for method signin
	
	
3. Create socket object (using token):


	```javascript

	var socket = io('http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/api?token=[token]');

	```


4. You should specify the room (rooms):

	
	```javascript
	
    var room = "test_room"; (*example*)
	
	```
	

5. For joining to that room send message to socket(join_room):
	
	
	```javascript
	
	socket.emit('join_room', {room:room});
	
	```


6. We can listen log of our activity("notifications"):
	
	
	```javascript
	
	socket.on('notifications', function(data){
		$("#responses").html("<p>Status: "+data.status+". "+data.description+"</p>");
	});
	
	```	
	
	> In response JSON we can get "status" parameter and "description" parameter so we can react to.
	> Also response data contains "request_type" field it helps defined response type. For now API supports request_type with values: "operation" and "invite"


7. To handle with "sending messages" operation just send message to socket(send_message) using token and room:
	- For example we want to send some text from "keypress" event
	
	
	
	```javascript
	
	$("#messager input").keypress(function(e){
		if(e.which == 13){
			var message = $(this).val();
			socket.emit("send_message", {room:room, msg:message});
		}
	});
	
	```


8. Also we should listen and wait for new messages in our room
	
	
	```javascript
	
	socket.on('get_messages', function(data){
		var message_display = $("#messager ul");
		$.each(data, function(i, val){
			message_display.append("<li><p><img [src]='"+val.avatar+"'></p><p>"+val.name+"</p><p>"+val.msg+"</p></li>");			
		});
	});
	
	```
	
	
	> Our response data is array of messages(objects)
	> if we will join multiple rooms we should sort our response data, so check val.path that contains room;

	
<h3>Invitation for chat</h3>

1. Send message to socket for triggering invitation event <b>('invite_to_chat')</b>
	
	```javascript
	
	var uid = //;
	socket.emit('invite_to_chat', {uid:uid});
	
	```

	> On this step we should identify the user we want to speak with ( for now it is "user id" )
	
2. Users should listen for possible invitations so developer who uses API should listen and catch notifications messages <b>('notifications')</b>.
   
	```javascript
	
	socket.on('notifications', function(data){
		// For now only invite to chat
		if(data.request_type == 'invite'){
			// So we need to react for such invitation
		}else if(data.request_type == 'operation'){
			// This data is just responses of different operations (display - optional)
		}
	});
	
	```
	
	> It is important to understand and handle the type of response notifications!
	> "request_type" for now contains only 2 value "invite" or "operations"
	> Developers should handle invite case and notify users with some Prompt window or Confirm, or ...

3. On this step user received a notification for inviting. So after he'll accept it we should send a message about successful accepting <b>('accept_invitaion')</b>

	```javascript
		
		...
		if(data.request_type == 'invite'){
			var reponse = confirm('You have recieved an invitation from  '+data.name + ". Accept?");
			if(reponse){
				// Accept invitation
				socket.emit('accept_invitaion', {room: data.room}); // 
			}
		}
		...
		
	```
	
	> Response data contains field - "room" - <b>data.room<b>, which we will use for sending messages
	
4. The form of sending messages is still the same

	```javascript
	
	...
	socket.emit("send_message", {room:room, msg:message});
	...
	
	```