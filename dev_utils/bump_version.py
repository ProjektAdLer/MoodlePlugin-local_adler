"""Bump the version number in version.php by one.
If date part is not from today, set it to today and set part_number to 00
"""

import fileinput
import sys
import datetime

version_file = "/home/markus/moodle/local/adler/version.php"

for line in fileinput.input(version_file, inplace=True):
    if line.startswith("$plugin->version"):
        version = line.split("=")[1].strip()[:-1]

        part_date = version[:8]
        part_number = version[8:]

        old_date = datetime.datetime.strptime(part_date, "%Y%m%d")
        # check if this is today
        if (old_date.date() == datetime.date.today()):
            # increment part_number
            part_number = str(int(part_number) + 1)
            # to string with leading zeros
            part_number = part_number.zfill(2)
        else:
            part_date = datetime.date.today().strftime("%Y%m%d")
            part_number = "00"

        print("$plugin->version = " + part_date + part_number + ";")


#         version += 1
#         print("$plugin->version = " + str(version) + ";")
    else:
        print(line, end='')
