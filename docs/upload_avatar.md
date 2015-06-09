## Upload avatar

```
<form action="http://pro.local/app/api" enctype="multipart/form-data" method="post" id="formaFileAvatar">
    <input type="file" name="novp_file"/>
    <input type="hidden" name="method" value="upload_avatar">                        
    <input type="hidden" name="token" value="13d125e7bcb2483e715e9f97f9d7a16f31f9ba0f">
    <input type="submit" value="upload_file">
</form>

```

> `value="13d125e7bcb2483e715e9f97f9d7a16f31f9ba0f"` - the user's token