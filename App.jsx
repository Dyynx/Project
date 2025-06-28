import { useState } from "react";
import "./App.css";
import logo from "../src/assets/hamburger.svg";

function App() {
  return (
    <>
      <div id="navigation" className="relative">
        <div className="satu flex p-2 justify-between">
          <div id="kotak_satu" className="flex w-90">
            <img src={logo} alt="logo" className=" w-8 h-8 self-center mr-1"/>
            <input type="text" className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-3/4 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 h-3/4 self-center" placeholder="Search..."/>
          </div>

          <label htmlFor="check" className="bg-gray-100 w-10 h-5  rounded-full relative cursor-pointer self-center border">
            <input type="checkbox" id="check" className="sr-only peer"/>
            <span className="w-3 h-3 bg-rose-300 absolute rounded-full left-1 top-1/2 -translate-y-1/2  peer-checked:bg-rose-600 peer-checked:left-[13.5px] transition-all duration-500"></span>
          </label>
        </div>

        <div className="dua w-1/2 bg-amber-300 h-[65vh] flex flex-col justify-evenly absolute items-center">
          <a href="">Home</a>
          <a href="">Daftar Komik</a>
          <a href="">Popular</a>
          <a href="">Project</a>
          <a href="">Bookmark</a>
          <a href="">History</a>
          <a href="">Login / Register</a>
        </div>
      </div>

      <div id="slider" className="">
        
      </div>

      <div id="hotAnimeUpdate">

      </div>

      <div id="badan">

        <div id="satu">
          <div id="new_release">

          </div>
        </div>

        <div id="dua">
          <div id="last_watch">

          </div>
          <div id="chat">

          </div>
          <div id="genre">

          </div>
          <div id="popular">

          </div>
        </div>

      </div>

      <div id="footer">

      </div>
    </>
  );
}

export default App;
