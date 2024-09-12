'use client'
import { FC, useState, useEffect } from 'react';
import { Button } from "@digdir/designsystemet-react";
import styles from './header-menu-content.module.scss'

interface HeaderMenuContentProps {}

const HeaderMenuContent: FC<HeaderMenuContentProps> = () => {
    const [drawerOpen, setDrawerOpen] = useState<boolean>(false);

    useEffect(() => {
        if (drawerOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'unset';
        }

        return () => {
            document.body.style.overflow = 'unset';
        };
    }, [drawerOpen]);

    const toggleDrawer = () => {
        setDrawerOpen(!drawerOpen);
    };

    return (
        <div>
            <Button onClick={toggleDrawer}>Open Menu</Button>
            <div className={`${styles.overlay} ${drawerOpen ? styles.active : ''}`} onClick={toggleDrawer}></div>
            <div id="mySidenav" className={`${styles.sidenav} ${drawerOpen ? styles.open : ''}`}>
                <button className={styles.closebtn} onClick={toggleDrawer}>&times;</button>
                <a href="#">About</a>
                <a href="#">Services</a>
                <a href="#">Clients</a>
                <a href="#">Contact</a>
            </div>
        </div>
    );
}

export default HeaderMenuContent