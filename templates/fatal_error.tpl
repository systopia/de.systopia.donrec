 <!DOCTYPE html>
<html>
 <head>
  <meta charset="UTF-8">
  <title>{$title}</title>
  {literal}
  <style>
  body {
      background-color: linen;
  }
  h1 {
      color: maroon;
  }
  .error {
    margin-left: 50px;
  }
  </style>
{/literal}
 </head>
 <body>
   <div class="error">
     <h1>{$headline}</h1>
     <p>{$description}</p>
   </div>
 </body>
</html>
