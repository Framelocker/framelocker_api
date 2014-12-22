Framelocker API Docs
========
<h4>API admin page - http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/novp/</h4>
<h4>API access - http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/api</h4>
<h4>Table 1.1 - API methods</h4>

 # | Method        | TYPE | Request                                                        | Response                              |
---|---------------| -----|----------------------------------------------------------------|---------------------------------------|
 1 | upload_file   | POST | {method:upload_file, token}                                    | {status, description, [title, src]}   |
 2 | get_files     | GET  | {method:get_files, token}                                      | {status, description,                 |
   |			   |	  |															 	   | [file_data:{id,user_id,bucket_id,     |
   |               |      |                                                        		   | filename,size,ext,aws,date}]}         |
 3 | register      | POST | {method:"register", params: {username, password, name, email}} | {status, description}                 |     
 4 | signin        | POST | {method:"signin", params:{login, pass}}                        | {status, description, <b>token</b>}   |
 5 | signout       | POST | {method:"signout", token}                                      | {status, description}                 |         
 6 | upload_avatar | POST | {method:"upload_avatar", token, novp_file}                     | {status, description, [filename]}     |          
 7 | set_name      | POST | {method:"set_name", token, params: {fstname, lstname}}         | {status, description}                 |   
 
<h4>Example of usage</h4>
<p>We need to know about user's files</p>
<p>Steps:</p>
<p><i>1. Authorization </i></p>
<p><i>2. Get user's files </i></p>

<p>1. Sending POST request to http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/api with data:{method:"signin", params:{"Alexey", "1234"}}</p>
<p>2. Catching response from API and obtaining <b>token</b> <i>For now when we create a new user, his token doesn't exist and he can get token manually from admin page (Settings), clicking refresh button. And after that the api method [signin] works fine for giving back token</i></p>
<p>3. Using token for method [get_files] - http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/api?method=get_files&token=91c26f0fec6f834d928fcc644ef8532849803f77. We'll receive json with status(1-ok,0-error,...), description(Text for human) and json array with file's info</p>	


<h4>Small sample of code</h4>
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

Chat API Docs
NodeJS Socket
========
# Socket connection
<p>1. Enable socket script to site where API will be used :
	- <script type="text/javascript" src="http://[domain]/socket.io/socket.io.js"></script> 
	*([Domain] ec2-54-68-182-31.us-west-2.compute.amazonaws.com)
</p>
<p>2. Create socket object:
	- var socket = io('http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/api');
</p>
<p>3. Obtain token with authorization previous method (signin):
	- var token = data.token;
	*(where [data] json response for method signin)
</p>
<p>4. You should specify the room (rooms):
	- var room = "test_room"; (*example*)
</p>
<p>5. For joining to that room send message to socket(join_room):
	
	- socket.emit('join_room', {token:token, room:room});
	
</p>

<p>6. We can listen log of our activity(api_response):
	socket.on('api_response', function(data){
		$("#responses").html("<p>Status: "+data.status+". "+data.description+"</p>");
	});
	
	- In response JSON we can get "status" parameter and "description" parameter so we can react to.
</p>

<p>7. To handle with "sending messages" operation just send message to socket(send_message) using token and room:
	- For example we want to send some text from "keypress" event
	```
	$("#messager input").keypress(function(e){
		if(e.which == 13){
			var message = $(this).val();
			socket.emit("send_message", {token:token, room:room, msg:message});
		}
	});
	```
</p>

<p>8. Also we should listen and wait for new messages in our room
	
	
	
	socket.on('get_messages', function(data){
		var message_display = $("#messager ul");
		$.each(data, function(i, val){
			message_display.append("<li><p><img src='"+val.avatar+"'></p><p>"+val.name+"</p><p>"+val.msg+"</p></li>");			
		});
	});
	
	
	
	- Our response data is array of messages(objects)
	- *(if we will join multiple rooms we should sort our response data, so check val.path that contains room);
</p>

