reload.php is used by Survey Monkey as its exit page. While the survey is running, the iframe it is embedded in is cross domain and can't access the Moodle site, so it is unable to look up the URL of the page or get the correct course ID to redirect to. Some surveys are shared across multiple courses, so can't have a specific exit url attached.

reload.php is hosted in the original location https://cpd.avant.org.au/theme/avant/reload.php which is the same domain inside the iframe as the parent document and therefore can use script to cross the domain boundary to look up the course to redirect to. It follows these steps:

1. Wait until the document loads
2. Look for a link to the course homepage using jQuery (find all links to /course/view.php?id=X
3. Writes a buton to the page inviting the user to click to exit the survey which is targetted to the parent frame
4. Automatically clicking the button - if the javascript fails they can still click the link
5. The course loads in the frame parenting the iframe