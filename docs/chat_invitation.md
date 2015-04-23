# NodeJS Chat

## 

### Socket connection

#### Development Socket - 8081
#### Production Socket - 8082

 *  Enable socket script to site where API will be used:

```javascript
<script type="text/javascript" src="http://chat.framelocker.com:8081/socket.io/socket.io.js"></script> 
```
> - [Domain] - http://chat.framelocker.com - Domain where NodeJS server installed

 * Obtain token with authorization method [`signin`] using server side API:
 
```javascript
	var token = data.token;		 
```

* Create socket object (using token):

```javascript
	var socket = io('chat.framelocker.com:8081/api?token=[token]');
``` 
### Any further actions developer should start after successful authorization

```javascript
socket.on('notifications', function(data){
	if(data.request_method == 'connection' && data.status == 1){
		... // Success
	}
}
``` 

### Connecting to room
* You should specify the room (rooms):

```javascript	
  var room = "test_room"; (`example`)	
```

* For joining to that room send message to socket(join_room):

```javascript	
  socket.emit('join_room', {room:room});
```

* We can listen log of our activity("notifications"):

```javascript	
	socket.on('notifications', function(data){
		$("#responses").html("<p>Status: "+data.status+". "+data.description+"</p>");
	});	
```	

>  - In response JSON we can get "status" parameter and "description" parameter so we can react to.

> - Also response data contains "request_type" field it helps defined response type. For now API supports request_type with values: "operation" and "invite"

### Send Message

* To handle with "sending messages" operation just send message to socket(send_message) using token and room:
> - For example we want to send some text from "keypress" event


```javascript	
	$("#messager input").keypress(function(e){
		if(e.which == 13){
			var message = $(this).val();
			socket.emit("send_message", {room:room, msg:message});
		}
	});	
```

### Listen for recieved messages

* Also we should listen and wait for new messages in our room

```javascript	
	socket.on('get_messages', function(data){
		var message_display = $("#messager ul");
		$.each(data, function(i, val){
			message_display.append("<li><p><img [src]='"+val.avatar+"'></p><p>"+val.name+"</p><p>"+val.msg+"</p></li>");			
		});
	});	
```

>  - Our response data is array of messages(objects)

>  - If we will join multiple rooms we should sort our response data, so check val.path that contains room;

### Invites (General)

* It is possible to invite people with `room` or ~~without~~ (Unavailable for now).
* In case 2 - API will generate room name for you
* ~~Using static room name will help us to catch previous message history~~ (Unavailable for now)

### Invite to Single User

* Send message to socket for triggering invitation event `invite_to_chat`

```javascript	
	var uid = //;
	socket.emit('invite_to_chat', {uid:uid});	
```

> - On this step we should identify the user we want to speak with 

* Users should listen for possible invitations so developer who uses API should listen and catch notifications messages `notifications`

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

>  - It is important to understand and handle the type of response notifications!

> - "request_type" for now contains only 2 value "invite" or "operations"

>  - Developers should handle invite case and notify users with some Prompt window or Confirm, or ...

* On this step user received a notification for inviting. So after he'll accept it we should send a message about successful accepting `accept_invitaion`

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

>  - Response data contains field - "room" - `data.room`, which we will use for sending messages

* Handling accepting invitation Event:

```javascript
socket.on('notifications', function(data){
  if(data.type == "accepted_invitation"){
      $("#panelGeneral").addClass("hide");
      $("#panelPM").removeClass("hide");
      var udata = data.params.userData;	
      ...
```
> - Data of `notifications` holds User Data `data.params.userData` and room name `data.params.room`

* The form of sending messages is still the same

```javascript	
	...
	socket.emit("send_message", {room:room, msg:message});
	...	
```

### Get room after triggering invite Event

* After triggering `invite_to_chat` event we should obtain room name for further operations

```javascript	
if(data.request_type == 'invitation_send'){
  	var room = data.params.room;
	...
}
```

### Group Invitation

* For group invite just define array of user IDs and use `invite_to_chat`:

```javascript
var uids = [id1, id2 ... idn];
socket.emit('invite_to_chat', {uid:uids});
```