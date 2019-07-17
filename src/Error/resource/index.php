
<!DOCTYPE html>
<html>
    <head>
        <title>Application Error</title>
        <style>
            
            body{
                font-family: Segoe UI;
                color: brown;
            }
            h1{
                font-size: 22px;
            }
            .container{
                width:auto;
                background-color: #F0F0F0;
                border: 1px solid #cccccc;
                border-radius: 8px;
                padding: 20px;
                margin-left: 8%;
                margin-right: 8%;
                height: auto;
                
            }
            .trace{
                background-color: #fdfbfb;               
                font-size: 12px;
                height: auto;
                padding: 10px;
                border: 1px solid #cccccc;;
                border-radius: 8px;
            
            }
            .message{
                background-color:  #fdfbfb;
                font-size: 20px;
                font-weight:600;
                margin-bottom: 20px;
                height: auto;
                padding: 20px;
                border: 1px solid #cccccc;;
                border-radius: 8px;
            }
            .details{
                background-color:  #fdfbfb;
                font-size: 16px; 
                height: auto;
                text-height:max-size;
                border: 1px solid #cccccc;
                padding: 20px;
                border-radius: 8px;
            }
        </style>
    </head>
    
    <body>
        <div class="container">

            <div class="message">
                <?=$message?>
            </div>
            <div class="content"><?= $content ?></div>				

        </div>	
    </body>
</html>	