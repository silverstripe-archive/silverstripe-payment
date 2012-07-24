## PayPal Payment

# Configuration guide: </br>
  Add to mysite/_config:
    PayPalGateway: 
      dev: // to be added only if Sandbox is used
        url: 
          'https://api-3t.sandbox.paypal.com/nvp'
        authentication:
          username:
          password:
          signature: 
      live:
        url: 
          'https://api-3t.paypal.com/nvp'
        authentication:
          username:
          password:
          signature: 
