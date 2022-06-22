use sha1collisiondetection::Sha1CD;
use std::io;

fn main() -> io::Result<()> {
    let mut hasher = Sha1CD::default();

    let mut file = io::stdin();

    io::copy(&mut file, &mut hasher)?;
    let result = hasher.finalize_cd();

    match result {
        Ok(_) => {
            std::process::exit(0);
        }
        Err(_) => {
            std::process::exit(3);
        }
    }
}
