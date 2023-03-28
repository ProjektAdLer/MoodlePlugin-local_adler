import argparse
import requests
import base64

argParser = argparse.ArgumentParser()
argParser.add_argument("-f", "--file", help="filepath to mbz to upload", required=True)
argParser.add_argument("-t", "--token", help="auth token", required=True)
argParser.add_argument("-u", "--url", help="moodle url (default: http://localhost)", default="http://localhost")


args = argParser.parse_args()




with open(args.file, "rb") as image_file:
    encoded_string = base64.b64encode(image_file.read())


request_params = {
    'wstoken': args.token,
    'wsfunction': 'local_adler_upload_course',
    'moodlewsrestformat': 'json',
    'mbz': encoded_string
}

response = requests.post(
    args.url + '/webservice/rest/server.php',
    data=request_params
)


print(response.status_code)
print(response.text)