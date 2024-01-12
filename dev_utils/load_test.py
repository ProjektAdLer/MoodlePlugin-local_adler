
import time
import argparse
import requests
from multiprocessing import Process


argParser = argparse.ArgumentParser()
argParser.add_argument("-b", "--backupFile", help="", required=True)
argParser.add_argument("-a", "--atfFile", help="", required=True)
argParser.add_argument("-t", "--token", help="auth token", required=True)
argParser.add_argument("-u", "--url", help="moodle url (default: http://localhost)", default="http://localhost")
argParser.add_argument("-c", "--count", help="how often the file will be uploaded", default=10)
argParser.add_argument("-d", "--delay", help="delay between starting uploads", default=5)

args = argParser.parse_args()

def upload_file():
    print("Uploading backup file")
    backupFile = open(args.backupFile, 'rb')
    atfFile = open(args.atfFile, 'rb')

    response = requests.post(
        args.url + '/api/Worlds',
        files={'backupFile': backupFile.read(), 'atfFile': atfFile.read()},
        headers={'token': args.token},
    )

    print(response.status_code)
    print(response.text)

if __name__ == "__main__":
    processes = []
    for i in range(int(args.count)):
        p = Process(target=upload_file)
        p.start()
        processes.append(p)
        time.sleep(int(args.delay))
    for p in processes:
        p.join()
